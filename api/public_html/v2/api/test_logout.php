<!DOCTYPE html>
<html>
<head>
    <title>Logout Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        button { padding: 10px 20px; margin: 5px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .result { background: #e6f3ff; padding: 10px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h2>Logout Debug Test</h2>
    
    <div class="test-section">
        <h3>1. Set Test Session Data</h3>
        <button onclick="setTestSession()">Set Test Session</button>
        <div id="set-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>2. Check Current Session Data</h3>
        <button onclick="checkSession()">Check Session</button>
        <div id="check-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>3. Test Logout Function</h3>
        <button onclick="testLogout()">Test Logout</button>
        <div id="logout-result" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>4. Verify Complete Cleanup</h3>
        <button onclick="verifyCleanup()">Verify Cleanup</button>
        <div id="verify-result" class="result"></div>
    </div>

    <script>
        function setTestSession() {
            localStorage.setItem('session_token', 'test_token_12345');
            localStorage.setItem('user_email', 'test@example.com');
            localStorage.setItem('user_plan', 'Free');
            localStorage.setItem('remember_login', 'true');
            
            document.getElementById('set-result').innerHTML = '✅ Test session data set';
        }
        
        function checkSession() {
            const data = {
                session_token: localStorage.getItem('session_token'),
                user_email: localStorage.getItem('user_email'),
                user_plan: localStorage.getItem('user_plan'),
                remember_login: localStorage.getItem('remember_login'),
                localStorage_length: localStorage.length
            };
            
            document.getElementById('check-result').innerHTML = 
                '<strong>Current Session Data:</strong><br>' + 
                JSON.stringify(data, null, 2).replace(/\n/g, '<br>').replace(/ /g, '&nbsp;');
        }
        
        function testLogout() {
            // Simulate the exact logout function from profile.js
            localStorage.clear();
            sessionStorage.clear();
            
            // Clear cookies
            document.cookie.split(";").forEach(function(c) { 
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            });
            
            document.getElementById('logout-result').innerHTML = '✅ Logout function executed';
        }
        
        function verifyCleanup() {
            const remainingData = {
                session_token: localStorage.getItem('session_token'),
                user_email: localStorage.getItem('user_email'),
                user_plan: localStorage.getItem('user_plan'),
                remember_login: localStorage.getItem('remember_login'),
                localStorage_length: localStorage.length,
                sessionStorage_length: sessionStorage.length
            };
            
            const isClean = !remainingData.session_token && 
                           !remainingData.user_email && 
                           remainingData.localStorage_length === 0;
            
            document.getElementById('verify-result').innerHTML = 
                '<strong>Cleanup Verification:</strong><br>' + 
                JSON.stringify(remainingData, null, 2).replace(/\n/g, '<br>').replace(/ /g, '&nbsp;') +
                '<br><br><strong>Status: ' + (isClean ? '✅ CLEAN' : '❌ DATA REMAINS') + '</strong>';
        }
        
        // Auto-check session on page load
        window.onload = function() {
            checkSession();
        };
    </script>
</body>
</html>
