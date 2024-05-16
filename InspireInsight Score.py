import os
import time
import pandas as pd
from datetime import datetime, timedelta
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException

# Set base folder for cache and spreadsheets
base_folder = 'c:\\InspireInsight\\'
cache_file = os.path.join(base_folder, 'InspireInsight_Scores.csv')

# Ensure base folder exists
os.makedirs(base_folder, exist_ok=True)

# Load scores from the CSV file into a cache
def load_cache_from_csv(file_path):
    if os.path.exists(file_path):
        df = pd.read_csv(file_path)
        cache = {row['Ticker']: (row['Score'], datetime.strptime(row['Date'], '%Y-%m-%d')) for index, row in df.iterrows()}
        return cache
    else:
        return {}

# Save the cache back to the CSV file
def save_cache_to_csv(cache, file_path):
    data = [{'Ticker': ticker, 'Score': score, 'Date': date.strftime('%Y-%m-%d')} for ticker, (score, date) in cache.items()]
    df = pd.DataFrame(data)
    df.to_csv(file_path, index=False)

# Check if the score is recent (within 3 months)
def is_recent(date, months=3):
    return datetime.now() - date < timedelta(days=30*months)

# Set up WebDriver
def setup_webdriver():
    chrome_options = webdriver.ChromeOptions()
    chrome_options.add_argument("--headless")  # Run in headless mode
    chrome_options.add_argument("--log-level=3")  # Suppress output
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--no-sandbox")
    driver = webdriver.Chrome(options=chrome_options)
    return driver

def get_inspire_insight_score(ticker, cache, file_path, retries=3):
    # Check if the score is in the cache and recent
    if ticker in cache and is_recent(cache[ticker][1]):
        return cache[ticker][0]
    
    driver = setup_webdriver()
    
    try:
        for attempt in range(retries):
            try:
                # Open the URL
                driver.get(f"https://inspireinsight.com/{ticker}/US")
                
                # Wait until the desired element is present using the data-cy attribute
                score_element = WebDriverWait(driver, 30).until(
                    EC.presence_of_element_located((By.CSS_SELECTOR, "[data-cy='impact-score']"))
                )
                
                # Extract the score
                score = score_element.text
                
                # Update the cache
                cache[ticker] = (score, datetime.now())
                save_cache_to_csv(cache, file_path)
                
                return score
            
            except TimeoutException:
                if attempt < retries - 1:
                    print(f"Retry {attempt + 1}/{retries} for ticker {ticker}")
                    time.sleep(2 ** attempt)  # Exponential backoff
                else:
                    print(f"Timeout occurred while waiting for the element for ticker {ticker}.")
                    return None
    finally:
        driver.quit()

def get_forbes_growth_stocks():
    driver = setup_webdriver()
    tickers = []
    
    try:
        # Open the Forbes URL
        driver.get("https://www.forbes.com/advisor/investing/best-growth-stocks/")
        
        # Wait for the stock elements to load
        stock_elements = WebDriverWait(driver, 30).until(
            EC.presence_of_all_elements_located((By.CSS_SELECTOR, "td.wysiwyg-editor .cell-content div"))
        )
        
        # Extract the ticker symbols
        for element in stock_elements:
            text = element.text.strip()
            if "(" in text and ")" in text:
                ticker = text.split("(")[-1].strip(")")
                tickers.append(ticker)
    
    except TimeoutException:
        print("Timeout occurred while waiting for the Forbes page to load.")
    
    finally:
        driver.quit()
    
    return tickers

def get_fool_growth_stocks():
    driver = setup_webdriver()
    tickers = []
    
    try:
        # Open the Motley Fool URL
        driver.get("https://www.fool.com/investing/stock-market/types-of-stocks/growth-stocks/")
        
        # Wait for the stock elements to load
        stock_elements = WebDriverWait(driver, 30).until(
            EC.presence_of_all_elements_located((By.CSS_SELECTOR, "table tbody tr th a"))
        )
        
        # Extract the ticker symbols from the 'a' tags within the table rows
        for element in stock_elements:
            href = element.get_attribute("href")
            ticker = href.split("/")[-2].split(":")[-1]
            tickers.append(ticker)
    
    except TimeoutException:
        print("Timeout occurred while waiting for the Motley Fool page to load.")
    
    finally:
        driver.quit()
    
    return tickers

def main():
    # Load the cache from the CSV file
    cache = load_cache_from_csv(cache_file)
    
    # Get the list of Forbes growth stocks
    forbes_tickers = get_forbes_growth_stocks()
    
    # Get the list of Motley Fool growth stocks
    fool_tickers = get_fool_growth_stocks()
    
    # Combine both lists, removing duplicates
    tickers = list(set(forbes_tickers + fool_tickers))
    
    # Get the Inspire Insight Score for each ticker
    scores = {}
    for ticker in tickers:
        score = get_inspire_insight_score(ticker, cache, cache_file)
        if score is not None and score.isdigit() and int(score) > 0:
            scores[ticker] = score
        # Adding sleep to prevent being blocked by too many requests in a short time
        time.sleep(5)
    
    # Output the results for positive scores
    for ticker, score in scores.items():
        print(f"{ticker}: {score}")

if __name__ == "__main__":
    main()
