import os
import shutil

# Path to the main CSS file
main_css_path = os.path.join('css', 'style.css')

# Base directory containing all modules
base_dir = 'ru'

# Function to read file with different encodings
def read_file_with_encodings(file_path):
    encodings = ['utf-8', 'windows-1251', 'cp1251', 'iso-8859-1', 'latin1']
    
    for encoding in encodings:
        try:
            with open(file_path, 'r', encoding=encoding) as f:
                return f.read(), encoding
        except UnicodeDecodeError:
            continue
    
    # If all encodings fail, try with error handling
    try:
        with open(file_path, 'r', encoding='utf-8', errors='replace') as f:
            return f.read(), 'utf-8'
    except Exception as e:
        print(f"Error reading {file_path} with any encoding: {str(e)}")
        return None, None

# Function to update CSS in a module
def update_module_css(module_path):
    try:
        # Create css directory in the module if it doesn't exist
        css_dir = os.path.join(module_path, 'css')
        os.makedirs(css_dir, exist_ok=True)
        
        # Copy the main CSS file to the module's css directory
        shutil.copy2(main_css_path, css_dir)
        
        # Update the CSS link in index.html
        index_file = os.path.join(module_path, 'index.html')
        if os.path.exists(index_file):
            content, encoding = read_file_with_encodings(index_file)
            
            if content is None:
                print(f"  Could not read {index_file}, skipping...")
                return
            
            # Replace the CSS link to use the local copy
            new_content = content.replace(
                '<link rel="stylesheet" href="../../css/style.css">',
                '<link rel="stylesheet" href="css/style.css">'
            )
            
            # Write the updated content back to index.html with original encoding
            with open(index_file, 'w', encoding=encoding or 'utf-8') as f:
                f.write(new_content)
            
            print(f"  Successfully updated {index_file}")
        else:
            print(f"  No index.html found in {module_path}")
            
    except Exception as e:
        print(f"  Error processing {module_path}: {str(e)}")

# Main execution
print("Starting CSS update for all modules...")

# Get all module directories
for item in sorted(os.listdir(os.path.join(base_dir))):
    module_path = os.path.join(base_dir, item)
    if os.path.isdir(module_path) and item.startswith('module-'):
        print(f"\nProcessing {item}...")
        update_module_css(module_path)

print("\nCSS update complete for all modules!")
