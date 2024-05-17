import pandas as pd

def convert_excel_to_csv():
    # Prompt user for input file path
    input_file_path = input('Please enter the path to the Excel file (including quotation marks): ').strip('"')

    # Load the Excel file
    try:
        excel_data = pd.ExcelFile(input_file_path)
    except FileNotFoundError:
        print(f"File not found: {input_file_path}")
        return
    except Exception as e:
        print(f"Error loading Excel file: {e}")
        return

    # Initialize an empty DataFrame to store all data
    all_data = pd.DataFrame()

    # Iterate through each sheet and append the relevant columns to all_data
    for sheet_name in excel_data.sheet_names:
        df = pd.read_excel(excel_data, sheet_name=sheet_name)
        
        # Check if necessary columns are present
        if 'Ticker' in df.columns and 'Upcoming Score' in df.columns:
            relevant_data = df[['Ticker', 'Upcoming Score']]
            all_data = pd.concat([all_data, relevant_data])
        else:
            print(f"Skipping sheet '{sheet_name}' as it does not contain the required columns.")
    
    # Check if data is not empty
    if not all_data.empty:
        # Save the combined data to a CSV file
        output_file_path = input_file_path.replace('.xlsx', '_combined.csv')
        all_data.to_csv(output_file_path, index=False)
        print(f"Data successfully saved to {output_file_path}")
    else:
        print("No relevant data found in any sheets.")

if __name__ == "__main__":
    convert_excel_to_csv()
