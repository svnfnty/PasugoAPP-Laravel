# Fix Laravel Reverb Configuration

## Steps:
- [x] Fix `config/broadcasting.php` - Add default values for Reverb connection
- [x] Fix `config/reverb.php` - Add default values for apps configuration
- [x] Fix `public/js/map.js` - Strip protocol from REVERB_HOST to fix WebSocket URL
- [x] Fix `resources/views/riders/dashboard.blade.php` - Strip protocol from wsHost
- [x] Fix `resources/views/rider/dashboard.blade.php` - Strip protocol from wsHost
- [x] Fix `resources/views/client/dashboard.blade.php` - Strip protocol from wsHost
- [x] Create `config/reverb_bootstrap.php` - Define PCNTL constants for environments without the extension
- [x] Update `config/reverb.php` - Include bootstrap file
- [x] Update `bootstrap/app.php` - Load bootstrap file early in application lifecycle
- [ ] Verify the fixes allow `php artisan reverb:install` and `php artisan reverb:start` to complete

## Issues Found:
1. Missing default values for REVERB_APP_KEY, REVERB_APP_SECRET, REVERB_APP_ID, REVERB_HOST
2. WebSocket URL malformed due to protocol being included in REVERB_HOST
3. PCNTL extension not available (SIGINT, SIGQUIT, SIGTERM, SIGUSR1, SIGUSR2, SIGALRM constants undefined)

## Changes Made:
- Added default values to `config/broadcasting.php`:
  - REVERB_APP_KEY: 'local-key'
  - REVERB_APP_SECRET: 'local-secret'
  - REVERB_APP_ID: 'local-app-id'
  - REVERB_HOST: 'localhost'
  - REVERB_PORT: 8080
  - REVERB_SCHEME: 'http'

- Added default values to `config/reverb.php`:
  - REVERB_APP_KEY: 'local-key'
  - REVERB_APP_SECRET: 'local-secret'
  - REVERB_APP_ID: 'local-app-id'
  - REVERB_HOST: 'localhost'
  - REVERB_PORT: 8080
  - REVERB_SCHEME: 'http'
  - hostname: 'localhost'

- Fixed `public/js/map.js`:
  - Added protocol stripping logic to handle REVERB_HOST containing "https://" prefix
  - This fixes the malformed WebSocket URL: `wss://https//host:8080` -> `wss://host:8080`

- Fixed all dashboard blade files:
  - `resources/views/riders/dashboard.blade.php`
  - `resources/views/rider/dashboard.blade.php`
  - `resources/views/client/dashboard.blade.php`
  - All now strip protocol from wsHost before passing to Echo

- Created `config/reverb_bootstrap.php`:
  - Defines SIGINT, SIGTERM, SIGTSTP, SIGQUIT, SIGUSR1, SIGUSR2, SIGALRM constants if not available
  - Provides no-op implementations for pcntl_signal and pcntl_async_signals
  - Allows Reverb to run in environments without PCNTL extension

- Updated `bootstrap/app.php`:
  - Loads bootstrap file at the very beginning before Application is created
  - Ensures constants are defined before Symfony Console initializes
