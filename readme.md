# 🔐 Security Features

## Protection Against Common Attacks

### 🛡️ SQL Injection Prevention
- PDO prepared statements for all database queries
- User input never executed as raw SQL

### 🛡️ CSRF Protection  
- Unique tokens generated per session
- All forms protected against forgery

### 🛡️ XSS Protection
- Output escaped with `htmlspecialchars()`
- Input sanitized before processing

### 🔐 Password Security
- Passwords hashed with bcrypt
- Strength requirement: Medium or Strong only
- Real-time password strength checker

### ⚡ Rate Limiting
- Login attempts limited to prevent brute force
- Registration attempts monitored

### ✅ Input Validation
- Server-side validation for all inputs
- Username length: 3-20 characters
- Password must meet complexity requirements

## Security Headers
- Clickjacking protection enabled
- MIME sniffing prevention
- XSS protection in browsers

## Session Security
- Sessions regenerated on login
- Secure session handling

---

