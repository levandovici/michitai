# Multiplayer API Debugging Guide

## Overview
This guide provides comprehensive debugging information for the Multiplayer API, including error codes, debug points, and troubleshooting steps for common issues.

## Error Code System

### Error Code Categories
- **1000-1999**: Authentication Errors
- **2000-2999**: Registration Errors  
- **3000-3999**: API Errors
- **4000-4999**: Database Errors
- **5000-5999**: System Errors
- **6000-6999**: Payment Errors
- **7000-7999**: Game Logic Errors

### Common Error Codes

#### Registration Issues
- **2001**: Invalid email format
- **2002**: Email already exists
- **2003**: Password doesn't meet requirements
- **2006**: Database error during registration
- **2007**: Registration data validation failed

#### API Issues
- **3001**: HTTP method not allowed
- **3003**: Invalid JSON format in request
- **3004**: Missing required headers

#### System Issues
- **5001**: Internal server error
- **5003**: Server configuration error

## Debug Points in Registration Flow

### Frontend Debug Points (register.html)
1. **Input Validation**: Check email format and password strength
2. **Request Preparation**: Log request data (password hidden)
3. **Network Request**: Log response status and headers
4. **Response Parsing**: Check for empty responses and JSON parsing errors
5. **Success/Error Handling**: Log final result and redirect

### Backend Debug Points (API)
1. **HTTP Method Check**: Verify POST method
2. **JSON Parsing**: Validate request body format
3. **Field Validation**: Check required email/password fields
4. **Auth Service Call**: Log registration attempt
5. **Response Generation**: Log final API response

### Database Debug Points (Auth.php)
1. **Input Validation**: Email format and password strength
2. **Duplicate Check**: Verify email doesn't exist
3. **User Creation**: Database insert operation
4. **Token Generation**: API and verification tokens
5. **Email Simulation**: Development email sending

## Common Issues and Solutions

### "Unexpected end of JSON input" Error

**Symptoms:**
- Frontend shows JSON parsing error
- Empty response from server
- Registration fails silently

**Debug Steps:**
1. Check browser console for detailed error logs
2. Verify API endpoint is accessible (check network tab)
3. Check server error logs for PHP errors
4. Verify database connection and table creation

**Solutions:**
- Ensure web server is running and configured properly
- Check file permissions on API directory
- Verify database file can be created/accessed
- Check PHP error logs for syntax or runtime errors

### Database Connection Issues

**Symptoms:**
- Error code 4001 (DB_CONNECTION_FAILED)
- Registration fails with database error

**Debug Steps:**
1. Check if SQLite database file exists and is writable
2. Verify database directory permissions
3. Check PHP SQLite extension is installed

**Solutions:**
- Ensure `private/database/` directory exists and is writable
- Install PHP SQLite extension if missing
- Check file permissions (755 for directories, 644 for files)

### Missing Class Files

**Symptoms:**
- PHP fatal errors about missing classes
- API returns 500 internal server error

**Debug Steps:**
1. Check if all class files exist in `private/api_backend/classes/`
2. Verify include paths are correct
3. Check file permissions

**Solutions:**
- Ensure all required class files are present
- Use stub classes for development (automatically created)
- Verify file paths in API index.php

## Development vs Production

### Development Mode Features
- Detailed error logging with stack traces
- Automatic stub class creation
- Relaxed CORS headers
- SQLite database for easy setup
- Simulated email sending

### Production Mode Features
- Secure error messages (no sensitive data)
- Strict CORS headers
- MySQL database connection
- Real email sending via SMTP
- Enhanced security headers

## Logging and Monitoring

### Log Files
- **API Errors**: `private/logs/api_errors.log`
- **PHP Errors**: `private/logs/error.log`
- **Debug Output**: Browser console (development)

### Debug Information
When `DEBUG_MODE` is enabled, the following information is logged:
- Request/response data
- Database operations
- Class loading status
- Error stack traces
- API call flow

## Testing the API

### Manual Testing
1. Use the provided `test_api.php` script
2. Test via browser developer tools
3. Use Postman or similar API testing tool

### Test Registration Request
```bash
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"TestPassword123","newsletter":false}'
```

### Expected Success Response
```json
{
  "success": true,
  "message": "Registration successful. Please check your email to verify your account.",
  "data": {
    "user_id": 1,
    "email": "test@example.com",
    "api_token": "mapi_...",
    "email_verified": false,
    "verification_required": true
  },
  "timestamp": "2025-01-07T16:14:44+00:00"
}
```

### Expected Error Response
```json
{
  "success": false,
  "error_code": 2002,
  "error_message": "An account with this email already exists",
  "timestamp": "2025-01-07T16:14:44+00:00"
}
```

## Browser Console Debugging

### Enable Console Logging
The frontend includes comprehensive console logging when registration is attempted:

```javascript
// Check browser console for these debug messages:
console.log('RegisterPage: Starting registration process for:', email);
console.log('RegisterPage: Sending request data:', requestData);
console.log('RegisterPage: Response status:', response.status);
console.log('RegisterPage: Raw response text:', responseText);
console.log('RegisterPage: Parsed response:', result);
```

### Common Console Errors
- **Network Error**: Check if web server is running
- **CORS Error**: Verify CORS headers are properly set
- **JSON Parse Error**: Server returned non-JSON response
- **Empty Response**: API endpoint not found or crashed

## Troubleshooting Checklist

### Before Debugging
- [ ] Web server is running (Apache/Nginx/IIS)
- [ ] PHP is properly configured and working
- [ ] API directory has correct permissions
- [ ] Browser developer tools are open

### Registration Issues
- [ ] Check browser console for JavaScript errors
- [ ] Verify API endpoint returns valid JSON
- [ ] Check server error logs for PHP errors
- [ ] Test with simple curl command
- [ ] Verify database can be created/accessed

### API Issues
- [ ] Check HTTP status codes
- [ ] Verify request headers and body
- [ ] Test with different browsers
- [ ] Check network connectivity
- [ ] Verify CORS configuration

## Getting Help

If you're still experiencing issues after following this guide:

1. **Collect Debug Information**:
   - Browser console logs
   - Server error logs
   - Network request/response details
   - Error codes and messages

2. **Test Environment**:
   - Operating system and version
   - Web server type and version
   - PHP version
   - Browser type and version

3. **Reproduce the Issue**:
   - Exact steps to reproduce
   - Expected vs actual behavior
   - Frequency of the issue

This debugging system provides professional-level error tracking and troubleshooting capabilities for the Multiplayer API registration system.
