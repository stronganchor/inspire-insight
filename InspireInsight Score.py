from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException
import time
import os

def get_inspire_insight_score(ticker):
    # Initialize WebDriver with options to suppress output and run headless
    chrome_options = webdriver.ChromeOptions()
    chrome_options.add_argument("--headless")  # Run in headless mode
    chrome_options.add_argument("--log-level=3")  # Suppress output
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--no-sandbox")
    driver = webdriver.Chrome(options=chrome_options)  # Suppress more output
    
    try:
        # Open the URL
        driver.get(f"https://inspireinsight.com/{ticker}/US")
        
        # Wait until the desired element is present using the data-cy attribute
        score_element = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "[data-cy='impact-score']"))
        )
        
        # Extract the score
        score = score_element.text
        
    except TimeoutException:
        print(f"Timeout occurred while waiting for the element for ticker {ticker}.")
        score = None
    
    finally:
        driver.quit()
    
    return score

def get_forbes_growth_stocks():
    # Initialize WebDriver with options to suppress output and run headless
    chrome_options = webdriver.ChromeOptions()
    chrome_options.add_argument("--headless")  # Run in headless mode
    chrome_options.add_argument("--log-level=3")  # Suppress output
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--no-sandbox")
    driver = webdriver.Chrome(options=chrome_options)  # Suppress more output
    
    tickers = []
    
    try:
        # Open the Forbes URL
        driver.get("https://www.forbes.com/advisor/investing/best-growth-stocks/")
        
        # Wait for the stock elements to load
        stock_elements = WebDriverWait(driver, 10).until(
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

def main():
    # Get the list of Forbes growth stocks
    tickers = get_forbes_growth_stocks()
    
    # Get the Inspire Insight Score for each ticker
    scores = {}
    for ticker in tickers:
        score = get_inspire_insight_score(ticker)
        if score is not None and score.isdigit() and int(score) > 0:
            scores[ticker] = score
        # Adding sleep to prevent being blocked by too many requests in a short time
        time.sleep(1)
    
    # Output the results for positive scores
    for ticker, score in scores.items():
        print(f"{ticker}: {score}")

if __name__ == "__main__":
    main()
