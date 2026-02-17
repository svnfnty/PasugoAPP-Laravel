# 🛵 PasugoAPP - Real-Time Delivery & Rider Management System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/TailwindCSS-4.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="TailwindCSS">
  <img src="https://img.shields.io/badge/Laravel%20Reverb-Real--Time-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Reverb">
</p>

<p align="center">
  <strong>A modern, real-time delivery and rider management platform built with Laravel 12</strong>
</p>

## ✨ Features

### 🎯 Core Functionality
- **Dual Authentication System**: Separate login/registration for Clients and Riders
- **Real-Time Location Tracking**: Live rider location updates using WebSocket technology
- **Smart Order Management**: Complete order lifecycle from placement to delivery
- **Interactive Chat System**: Real-time messaging between clients and riders
- **Geospatial Queries**: Find nearby riders using Haversine formula for distance calculation

### 🚀 Real-Time Capabilities (Powered by Laravel Reverb)
- **Live Rider Tracking**: Watch riders move on the map in real-time
- **Instant Notifications**: Immediate order requests and responses
- **Bidirectional Chat**: Seamless communication between clients and riders
- **Status Updates**: Real-time order status changes and rider availability

### 📱 User Interfaces
- **Client Dashboard**: Place orders, track deliveries, chat with riders
- **Rider Dashboard**: Accept orders, update locations, manage deliveries
- **Interactive Map View**: Visual representation of available riders
- **Responsive Design**: Built with Tailwind CSS for all device sizes

## 🏗️ Architecture

### Database Schema
```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   clients   │────<│   orders    │>────│   riders    │
└─────────────┘     └─────────────┘     └─────────────┘
                           │
                    ┌──────┴──────┐
                    │  Chat Messages (Real-time via WebSockets)
                    └─────────────┘
```

### Key Models
- **Client**: Users who place delivery orders
- **Rider**: Delivery personnel with location tracking and status management
- **Order**: Delivery requests linking clients and riders

### Broadcasting Events
| Event | Description | Channel |
|-------|-------------|---------|
| `RiderLocationUpdated` | Real-time rider position updates | `riders` (public) |
| `RiderOrdered` | Notify rider of new order request | `rider.{id}` (private) |
| `RiderResponse` | Rider's response to order request | `client.{id}` (private) |
| `ChatMessage` | Bidirectional messaging | `chat.{type}.{id}` |
| `RiderRequestCancelled` | Order cancellation notification | `rider.{id}` |

## 🛠️ Tech Stack

| Component | Technology |
|-----------|------------|
| **Framework** | Laravel 12.x |
| **Language** | PHP 8.2+ |
| **Real-Time** | Laravel Reverb (WebSocket Server) |
| **Authentication** | Laravel Sanctum + Multi-guard |
| **Frontend** | Blade + Tailwind CSS 4 + Vite |
| **Database** | SQLite (default) / MySQL / PostgreSQL |
| **Testing** | PHPUnit |
| **Queue** | Laravel Queue (database driver) |

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (or MySQL/PostgreSQL)

### Quick Setup

```bash
# Clone the repository
git clone https://github.com/yourusername/PasugoAPP-Laravel.git
cd PasugoAPP-Laravel

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
touch database/database.sqlite
php artisan migrate --seed

# Build assets
npm run build

# Start the application (runs multiple services concurrently)
composer run dev
```

### Manual Service Startup

If you prefer to run services individually:

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Laravel Reverb (WebSocket server)
php artisan reverb:start

# Terminal 3: Queue worker
php artisan queue:listen --tries=1 --timeout=0

# Terminal 4: Vite development server
npm run dev

# Terminal 5: Real-time logs (optional)
php artisan pail --timeout=0
```

## ⚙️ Configuration

### Environment Variables

```env
# Application
APP_NAME=PasugoAPP
APP_ENV=local
APP_KEY=your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# Broadcasting (Laravel Reverb)
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Queue
QUEUE_CONNECTION=database
```

## 📡 API Endpoints

### Public API
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/riders` | List available riders (with optional geospatial filtering) |
| POST | `/api/riders/{id}/location` | Update rider location (demo endpoint) |

### Authenticated API (Sanctum)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/riders/{id}/order` | Request a rider for delivery |
| POST | `/api/clients/{clientId}/respond` | Rider responds to order request |

### Web Routes
| Route | Description |
|-------|-------------|
| `/client/login` | Client authentication |
| `/client/register` | Client registration |
| `/client/dashboard` | Client dashboard (auth required) |
| `/client/order/create` | Place new order (auth required) |
| `/client/riders/map` | View rider map (auth required) |
| `/rider/login` | Rider authentication |
| `/rider/register` | Rider registration |
| `/rider/dashboard` | Rider dashboard (auth required) |

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=FlowTest

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

## 🎯 Usage Flow

### Client Journey
1. **Register/Login** as a client
2. **Browse the map** to see available nearby riders
3. **Place an order** with pickup and delivery details
4. **Chat in real-time** with assigned rider
5. **Track delivery** status updates live

### Rider Journey
1. **Register/Login** as a rider (set vehicle type, bio)
2. **Go online** and start sharing location
3. **Receive order requests** in real-time
4. **Accept or decline** orders via chat interface
5. **Update order status** (accepted → picked up → delivered)
6. **Chat with clients** for coordination

## 🔒 Security Features

- **Multi-guard Authentication**: Separate auth systems for clients and riders
- **CSRF Protection**: All forms include CSRF tokens
- **API Token Authentication**: Sanctum for API security
- **Authorization Checks**: Controllers verify resource ownership
- **Input Validation**: Request validation on all endpoints
- **SQL Injection Protection**: Eloquent ORM parameter binding

## 🗺️ Geospatial Features

The application includes advanced location-based features:

```php
// Find riders within 5km radius using Haversine formula
$riders = Rider::selectRaw("id, name, lat, lng, status,
    (6371 * acos(cos(radians(?)) * 
    cos(radians(lat)) * 
    cos(radians(lng) - radians(?)) + 
    sin(radians(?)) * 
    sin(radians(lat)))) AS distance", 
    [$lat, $lng, $lat])
    ->having('distance', '<=', 5)
    ->orderBy('distance')
    ->get();
```

## 🛣️ Roadmap

- [ ] Mobile app companion (React Native/Flutter)
- [ ] Payment integration (Stripe/PayPal)
- [ ] Push notifications (Firebase)
- [ ] Route optimization (Google Maps API)
- [ ] Rating and review system
- [ ] Admin dashboard for analytics
- [ ] Multi-language support

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please read our [Contributing Guide](CONTRIBUTING.md) for details on code of conduct and development standards.

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The web framework used
- [Laravel Reverb](https://reverb.laravel.com) - Real-time WebSocket server
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [Vite](https://vitejs.dev) - Next-generation frontend tooling

---

<p align="center">
  <strong>Built with ❤️ using Laravel 12</strong>
</p>
