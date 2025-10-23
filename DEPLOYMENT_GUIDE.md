# Negative Keywords Automation System - Deployment Guide

## Overview
This guide provides step-by-step instructions for deploying the Negative Keywords Automation System in your Laravel application.

## Prerequisites
- Laravel 8+ application
- PHP 8.0+
- MySQL/PostgreSQL database
- Google Ads API access
- OpenAI API access
- Telegram Bot (optional, for notifications)

## Installation Steps

### 1. Database Setup

Run the migrations to create the required tables:
```bash
php artisan migrate
```

This will create:
- `new_terms_negative_0click` - Stores zero-click search terms
- `new_frasa_negative` - Stores individual phrases extracted from terms

**Database Schema Updates:**
- `notif_telegram` field is now an ENUM with values: 'sukses', 'gagal' (previously boolean)
- This field tracks Telegram notification status for each record

### 2. Environment Configuration

Copy the environment variables from `.env.example` to your `.env` file and configure:


**Important**: You need to generate a refresh token before the system can work. See the "Google Ads Authentication Setup" section below.

#### OpenAI Configuration
```env
OPENAI_API_KEY=your_openai_api_key
OPENAI_MODEL=gpt-3.5-turbo
OPENAI_MAX_TOKENS=150
OPENAI_TEMPERATURE=0.3
```

#### Telegram Configuration (Optional)
```env
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
```

#### Automation Settings
```env
NEGATIVE_KEYWORDS_ENABLED=true
NEGATIVE_KEYWORDS_BATCH_SIZE=50
NEGATIVE_KEYWORDS_MAX_RETRIES=3
NEGATIVE_KEYWORDS_AI_ENABLED=true
NEGATIVE_KEYWORDS_TELEGRAM_ENABLED=true
```

### 3. Google Ads Authentication Setup

Before testing the system, you need to generate a refresh token for Google Ads API authentication.

#### Step 1: Generate Refresh Token
Run the provided script to generate a refresh token:
```bash
php generate_refresh_token.php
```

This script will:
1. Generate an authorization URL for Google OAuth2
2. Prompt you to login with your Google account
3. Ask for consent to access Google Ads
4. Exchange the authorization code for a refresh token
5. Save the token to `storage/app/private/google_ads/refresh_token.txt`
6. Provide instructions to update your `.env` file

#### Step 3: Verify Authentication
Test the authentication setup:
```bash
# Test configuration and token
php artisan test:google-ads-connection --dry-run

# Test actual API connection
php artisan test:google-ads-connection
```

### 4. Service Configuration

#### Google Ads API Setup
1. Create a Google Ads API project in Google Cloud Console
2. Enable the Google Ads API
3. Create OAuth 2.0 credentials (Web application type)
4. Add authorized redirect URIs: `urn:ietf:wg:oauth:2.0:oob`
5. Generate a developer token from Google Ads account
6. Configure the credentials in your `.env` file
7. **Generate refresh token using the provided script** (see Authentication Setup section above)

#### OpenAI API Setup
1. Sign up for OpenAI API access
2. Generate an API key
3. Add the key to your `.env` file

#### Telegram Bot Setup (Optional)
1. Create a bot using @BotFather on Telegram
2. Get the bot token
3. Get your chat ID (you can use @userinfobot)
4. Configure in your `.env` file

### 4. Testing the System

#### Comprehensive Testing
Run the comprehensive test command:
```bash
php artisan negative-keywords:test-system
```

#### Step-by-Step Testing
Test the system components in order:

1. **Test Configuration**:
```bash
php artisan test:google-ads-connection --dry-run
```

2. **Test Google Ads Connection**:
```bash
php artisan test:google-ads-connection
```

3. **Test Data Fetching (Safe Mode)**:
```bash
# Test with small sample
php artisan safe:test-fetch --limit=3 --sample

# Test with filtering
php artisan safe:test-fetch --limit=5 --filter
```

4. **Test Individual Components**:
```bash
# Test database
php artisan negative-keywords:test-system --component=database

# Test Google Ads integration
php artisan negative-keywords:test-system --component=google-ads

# Test AI integration
php artisan negative-keywords:test-system --component=ai

# Test Telegram integration
php artisan negative-keywords:test-system --component=telegram
```

#### Available Testing Tools
- **TestGoogleAdsConnectionCommand** - Tests API connection and configuration
- **SafeTestFetchCommand** - Tests data fetching without database storage
- **SearchTermFetcher::testConnection()** - Read-only connection test method
- **SearchTermFetcher::testFetchZeroClickTerms()** - Limited fetch test method

### 5. Manual Testing

Test each command individually before enabling automation:

#### Fetch Zero-Click Terms
```bash
php artisan negative-keywords:fetch-terms --limit=10
```

#### Analyze Terms with AI
```bash
php artisan negative-keywords:analyze-terms --batch-size=5
```

#### Input Negative Keywords to Google Ads
```bash
php artisan negative-keywords:input-google --batch-size=5
```

#### Process Individual Phrases
```bash
php artisan negative-keywords:process-phrases --batch-size=10
```

### 6. Schedule Setup

The system uses Laravel's task scheduler. Add this cron job to your server:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path-to-your-project` with the actual path to your Laravel application.

### 7. Monitoring and Logs

#### Laravel Logs
Monitor the application logs for any errors:
```bash
tail -f storage/logs/laravel.log
```

#### Telegram Notifications
If configured, you'll receive Telegram notifications for:
- Successful operations
- Failed operations
- Daily summaries
- System errors

#### Database Monitoring
Check the database tables to monitor system activity:
```sql
-- Check recent terms
SELECT * FROM new_terms_negative_0click ORDER BY created_at DESC LIMIT 10;

-- Check AI analysis results
SELECT hasil_cek_ai, COUNT(*) FROM new_terms_negative_0click GROUP BY hasil_cek_ai;

-- Check Google Ads input status
SELECT status_input_google, COUNT(*) FROM new_terms_negative_0click GROUP BY status_input_google;
```

## System Architecture

### Workflow
1. **Fetch Terms** (Every 7 minutes): Retrieves zero-click search terms from Google Ads
2. **AI Analysis** (Every 7 minutes): Analyzes terms to determine relevance
3. **Input Keywords** (Every 7 minutes): Adds relevant terms as negative keywords
4. **Process Phrases** (Every 7 minutes): Extracts and processes individual phrases

### Services
- **SearchTermFetcher**: Handles Google Ads API interactions
- **TermAnalyzer**: Manages AI analysis using OpenAI
- **NotificationService**: Sends Telegram notifications

### Models
- **NewTermsNegative0Click**: Stores search terms and their processing status
- **NewFrasaNegative**: Stores individual phrases extracted from terms

## Troubleshooting

### Common Issues

#### 1. Google Ads API Errors
- Verify credentials in `.env`
- Check API quotas and limits
- Ensure proper OAuth setup

#### 2. OpenAI API Errors
- Verify API key
- Check usage limits and billing
- Monitor rate limits

#### 3. Database Connection Issues
- Verify database credentials
- Check table existence with migrations
- Monitor database logs

#### 4. Telegram Notification Issues
- Verify bot token and chat ID
- Check bot permissions
- Test with simple message first

### Performance Optimization

#### 1. Batch Processing
- Adjust `NEGATIVE_KEYWORDS_BATCH_SIZE` based on API limits
- Monitor processing times

#### 2. Rate Limiting
- Implement delays between API calls if needed
- Monitor API usage quotas

#### 3. Database Optimization
- Add indexes for frequently queried columns
- Implement data cleanup for old records

## Security Considerations

1. **API Keys**: Store all API keys securely in environment variables
2. **Database**: Use proper database user permissions
3. **Logs**: Avoid logging sensitive information
4. **Access Control**: Restrict access to admin commands

## Maintenance

### Daily Tasks
- Monitor system logs
- Check Telegram notifications
- Verify API quotas

### Weekly Tasks
- Review processed terms accuracy
- Clean up old database records
- Update AI prompts if needed

### Monthly Tasks
- Review system performance
- Update dependencies
- Backup configuration

## Support

For issues or questions:
1. Check the Laravel logs first
2. Run the test command to identify issues
3. Verify environment configuration
4. Check API service status

## Version History

- v1.0.0: Initial release with full automation system
  - Google Ads integration
  - OpenAI analysis
  - Telegram notifications
  - Automated scheduling