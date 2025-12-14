import os

def read_file_with_encodings(file_path):
    encodings = ['utf-8', 'windows-1251', 'cp1251', 'iso-8859-1', 'latin1']
    
    for encoding in encodings:
        try:
            with open(file_path, 'r', encoding=encoding) as f:
                return f.read(), encoding
        except UnicodeDecodeError:
            continue
    
    try:
        with open(file_path, 'r', encoding='utf-8', errors='replace') as f:
            return f.read(), 'utf-8'
    except Exception as e:
        print(f"  Error reading {file_path} with any encoding: {str(e)}")
        return None, None

def embed_css_in_module(module_path):
    try:
        index_file = os.path.join(module_path, 'index.html')
        css_file = os.path.join(module_path, 'css', 'style.css')
        
        if not os.path.exists(index_file):
            print(f"  index.html not found in {module_path}")
            return
            
        if not os.path.exists(css_file):
            print(f"  CSS file not found in {module_path}")
            return
        
        # Read the CSS content with proper encoding
        css_content, _ = read_file_with_encodings(css_file)
        if not css_content:
            print(f"  Could not read CSS file in {module_path}")
            return
            
        css_content = f'<style>\n{css_content}\n</style>'
        
        # Read the index.html content with proper encoding
        content, encoding = read_file_with_encodings(index_file)
        if not content:
            print(f"  Could not read index.html in {module_path}")
            return
        
        # Find the CSS link and replace it with embedded styles
        css_links = [
            '<link rel="stylesheet" href="css/style.css">',
            '<link rel="stylesheet" href="../../css/style.css">',
            '<link rel="stylesheet" href="/css/style.css">',
            '<link rel="stylesheet" href="style.css">'
        ]
        
        link_found = False
        for css_link in css_links:
            if css_link in content:
                content = content.replace(css_link, '')
                link_found = True
        
        if not link_found:
            print(f"  No CSS link found in {os.path.basename(module_path)}")
        
        # Insert the CSS right before the closing </head> tag
        if '</head>' in content:
            new_content = content.replace('</head>', f'{css_content}\n</head>')
            
            # Write the updated content back to index.html with original encoding
            with open(index_file, 'w', encoding=encoding or 'utf-8') as f:
                f.write(new_content)
            
            print(f"  Successfully embedded CSS in {os.path.basename(module_path)}")
        else:
            print(f"  No </head> tag found in {os.path.basename(module_path)}")
            
    except Exception as e:
        print(f"  Error processing {module_path}: {str(e)}")

# Main execution
print("Starting CSS embedding for all modules...")
base_dir = 'ru'

# Process each module
for item in sorted(os.listdir(os.path.join(base_dir))):
    module_path = os.path.join(base_dir, item)
    if os.path.isdir(module_path) and item.startswith('module-'):
        print(f"\nProcessing {item}...")
        embed_css_in_module(module_path)

print("\nCSS embedding complete for all modules!")
