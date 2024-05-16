from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException

def get_inspire_insight_score(ticker):
    # Initialize the WebDriver (adjust the executable_path to your actual path)
    driver = webdriver.Chrome()  # For Chrome, you can use ChromeDriver
    
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
        print("Timeout occurred while waiting for the element.")
        score = None
    
    finally:
        driver.quit()
    
    return score

# Example usage
ticker = "AAPL"
score = get_inspire_insight_score(ticker)
print(f"The Inspire Insight Score for {ticker} is: {score}")
