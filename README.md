# micropub.rocks
Micropub test suite and debugging utility

## Installation

**⚠️ This works in my env on macOS. Your experience may vary. Don't come 😭 to me if something breaks ⚠️**

1. Clone repo.
2. Copy `lib/config.template.php` to `lib/config.php` and set:
    - `$redis` to `tcp://redis:6379`,
    - `$dbhost` to `db:3306`,
    - `$base` to `http://localhost:5000/`,
    - ... and customise everything else if you want.
3. Copy `.env.example` to `.env` and customise to match any changes you've made in `lib/config.php`.
4. Ensure Docker for Mac is installed and running.
5. Start is all with `docker-compose up -d`.
6. Connect to <http://localhost:5000/>.

**ProTip:** If you're testing against a service you're developing locally, use the 
hostname of `docker.for.mac.localhost:<port>` for the micropub endpoint so the
micropub.rocks service knows where to send the requests.