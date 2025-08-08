<!DOCTYPE html>
<html>
<head>
    <title>Login Flow Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        button { padding: 10px 20px; margin: 5px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .result { background: #e6f3ff; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; }
        .error { background: #ffe6e6; }
        .success { background: #e6ffe6; }
    </style>
</head>
<body>
    <h2>Login Flow Debug Test</h2>
    
    <div class="test-section">
        <h3>1. Check Current Session State</h3>
        <button onclick="checkCurrentSession()">Check Session</button>
        <div id="session-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>2. Simulate Login Page Check</h3>
        <button onclick="simulateLoginPageCheck()">Simulate Login Page</button>
        <div id="login-check-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>3. Clear All Data (Simulate Logout)</h3>
        <button onclick="clearAllData()">Clear All Data</button>
        <div id="clear-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>4. Test Login Page After Clear</h3>
        <button onclick="testLoginAfterClear()">Test Login Page</button>
        <div id="after-clear-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>5. Go to Actual Login Page</h3>
        <button onclick="goToLoginPage()">Go to Login Page</button>
        <div id="goto-result" class="result"></div>
    </div>

    <script>
        function checkCurrentSession() {
            const sessionData = {
                session_token: localStorage.getItem('session_token'),
                user_email: localStorage.getItem('user_email'),
                user_plan: localStorage.getItem('user_plan'),
                remember_login: localStorage.getItem('remember_login'),
                localStorage_keys: Object.keys(localStorage),
                localStorage_length: localStorage.length
            };
            
            document.getElementById('session-result').innerHTML = 
                '<strong>Current Session:</strong><br>' + 
                JSON.stringify(sessionData, null, 2).replace(/\n/g, '<br>').replace(/ /g, '&nbsp;');
        }
        
        function simulateLoginPageCheck() {
            // This is the exact logic from login.html
            const token = localStorage.getItem('session_token');
            
            let result = '<strong>Login Page Logic:</strong><br>';
            result += 'session_token found: ' + (token ? '✅ YES' : '❌ NO') + '<br>';
            result += 'token value: ' + (token || 'null') + '<br>';
            
            if (token) {
                result += '<span class="error">🔄 WOULD REDIRECT TO PROFILE</span>';
            } else {
                result += '<span class="success">✅ WOULD SHOW LOGIN FORM</span>';
            }
            
            document.getElementById('login-check-result').innerHTML = result;
        }
        
        function clearAllData() {
            localStorage.clear();
            sessionStorage.clear();
            
            // Clear cookies
            document.cookie.split(";").forEach(function(c) { 
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            });
            
            document.getElementById('clear-result').innerHTML = '✅ All data cleared (simulating logout)';
        }
        
        function testLoginAfterClear() {
            const token = localStorage.getItem('session_token');
            
            let result = '<strong>After Clear Check:</strong><br>';
            result += 'session_token found: ' + (token ? '❌ STILL EXISTS' : '✅ CLEARED') + '<br>';
            result += 'localStorage length: ' + localStorage.length + '<br>';
            
            if (token) {
                result += '<span class="error">🚨 PROBLEM: Token still exists after clear!</span>';
            } else {
                result += '<span class="success">✅ GOOD: No token found, login form should show</span>';
            }
            
            document.getElementById('after-clear-result').innerHTML = result;
        }
        
        function goToLoginPage() {
            document.getElementById('goto-result').innerHTML = '🔄 Redirecting to login page...';
            setTimeout(() => {
                window.location.href = '../login.html';
            }, 1000);
        }
        
        // Auto-check on page load
        window.onload = function() {
            checkCurrentSession();
        };
    </script>
</body>
</html>
