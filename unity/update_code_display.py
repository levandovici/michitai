import os
import re

def update_module_code_display(module_path):
    try:
        index_file = os.path.join(module_path, 'index.html')
        
        if not os.path.exists(index_file):
            print(f"  index.html not found in {module_path}")
            return
            
        # Read the file content with proper encoding
        with open(index_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Extract all code blocks
        code_blocks = re.findall(r'<pre><code(?: class="language-csharp")?>(.*?)<\/code><\/pre>', content, re.DOTALL)
        
        if not code_blocks:
            print(f"  No code blocks found in {os.path.basename(module_path)}")
            return
        
        # Create tab buttons
        tab_buttons = ''
        tab_contents = ''
        
        for i, code in enumerate(code_blocks, 1):
            tab_id = f"code-{i}"
            tab_name = f"Code {i}"
            
            # Create tab button
            tab_buttons += f'<button class="tab-btn" onclick="showCodeTab(\'{tab_id}\', this)">{tab_name}</button>\n'
            # Create tab content
            tab_contents += f'''
            <div id="{tab_id}" class="code-tab">
                <div class="code-header">
                    <span class="code-title">{tab_name}</span>
                    <button class="copy-btn" onclick="copyCode('{tab_id}')">Copy</button>
                </div>
                <pre><code class="language-csharp">{code}</code></pre>
            </div>
            '''
        
        # Create the new code display HTML
        new_code_display = f'''
        <div class="code-display">
            <div class="code-tabs">
                {tab_buttons}
            </div>
            <div class="code-container">
                {tab_contents}
            </div>
        </div>
        '''
        
        # Replace all code blocks with the new display
        new_content = re.sub(
            r'<pre><code(?: class="language-csharp")?>.*?<\/code><\/pre>',
            new_code_display,
            content,
            flags=re.DOTALL,
            count=1  # Only replace the first occurrence
        )
        
        # Remove any remaining code blocks
        new_content = re.sub(
            r'<pre><code(?: class="language-csharp")?>.*?<\/code><\/pre>',
            '',
            new_content,
            flags=re.DOTALL
        )
        
        # Add the JavaScript for tab functionality
        js_code = '''
        <script>
            // Show the first tab by default
            document.addEventListener('DOMContentLoaded', function() {
                const firstTab = document.querySelector('.tab-btn');
                if (firstTab) firstTab.click();
            });

            function showCodeTab(tabId, element) {
                // Hide all tab contents
                const tabContents = document.querySelectorAll('.code-tab');
                tabContents.forEach(tab => {
                    tab.style.display = 'none';
                });

                // Remove active class from all buttons
                const tabButtons = document.querySelectorAll('.tab-btn');
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                });

                // Show the selected tab and activate the button
                const selectedTab = document.getElementById(tabId);
                if (selectedTab) {
                    selectedTab.style.display = 'block';
                    element.classList.add('active');
                }
            }

            function copyCode(tabId) {
                const codeBlock = document.querySelector(`#${tabId} code`);
                if (codeBlock) {
                    const textArea = document.createElement('textarea');
                    textArea.value = codeBlock.textContent;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    
                    // Show copied message
                    const copyBtn = document.querySelector(`#${tabId} .copy-btn`);
                    if (copyBtn) {
                        const originalText = copyBtn.textContent;
                        copyBtn.textContent = 'Copied!';
                        setTimeout(() => {
                            copyBtn.textContent = originalText;
                        }, 2000);
                    }
                }
            }
        </script>
        '''
        
        # Add the CSS for the code display
        css_code = '''
        <style>
            .code-display {
                margin: 2rem 0;
                background: #1e1e1e;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            }
            
            .code-tabs {
                display: flex;
                background: #252526;
                padding: 0.5rem 0.5rem 0 0.5rem;
                border-bottom: 1px solid #333;
                overflow-x: auto;
            }
            
            .tab-btn {
                background: #333;
                color: #9e9e9e;
                border: none;
                padding: 0.5rem 1rem;
                margin-right: 0.25rem;
                font-family: 'Fira Code', 'Consolas', monospace;
                font-size: 0.9rem;
                cursor: pointer;
                border-radius: 4px 4px 0 0;
                transition: all 0.2s ease;
            }
            
            .tab-btn:hover {
                background: #3c3c3c;
                color: #fff;
            }
            
            .tab-btn.active {
                background: #1e1e1e;
                color: #fff;
                border-bottom: 2px solid #4dabf7;
            }
            
            .code-container {
                position: relative;
            }
            
            .code-tab {
                display: none;
            }
            
            .code-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 1rem;
                background: #252526;
                border-bottom: 1px solid #333;
            }
            
            .code-title {
                color: #9e9e9e;
                font-size: 0.9rem;
                font-family: 'Fira Code', 'Consolas', monospace;
            }
            
            .copy-btn {
                background: #333;
                color: #9e9e9e;
                border: 1px solid #444;
                border-radius: 4px;
                padding: 0.25rem 0.75rem;
                font-size: 0.8rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .copy-btn:hover {
                background: #3c3c3c;
                color: #fff;
            }
            
            pre {
                margin: 0;
                padding: 1rem;
                overflow-x: auto;
            }
            
            code {
                font-family: 'Fira Code', 'Consolas', monospace;
                font-size: 0.95rem;
                line-height: 1.5;
            }
        </style>
        '''
        
        # Add the CSS and JS to the content
        if '</style>' in new_content:
            new_content = new_content.replace('</style>', f'{css_code}</style>')
        
        if '</body>' in new_content:
            new_content = new_content.replace('</body>', f'{js_code}</body>')
        
        # Write the updated content back to the file
        with open(index_file, 'w', encoding='utf-8') as f:
            f.write(new_content)
        
        print(f"  Updated code display in {os.path.basename(module_path)}")
            
    except Exception as e:
        print(f"  Error updating {os.path.basename(module_path)}: {str(e)}")

# Main execution
print("Updating code display for all modules...")
base_dir = 'ru'

# Process each module
for item in sorted(os.listdir(os.path.join(base_dir))):
    module_path = os.path.join(base_dir, item)
    if os.path.isdir(module_path) and item.startswith('module-'):
        print(f"\nProcessing {item}...")
        update_module_code_display(module_path)

print("\nCode display update complete for all modules!")
