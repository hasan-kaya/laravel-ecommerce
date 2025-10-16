# E-Commerce Platform

> E-commerce application built with Laravel, GraphQL and Clean Architecture.

## 📋 Table of Contents

- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [Elasticsearch Integration](#elasticsearch-integration)
- [Quick Start](#quick-start)

---

## 🏗️ Architecture

### Clean Architecture

**Principle:** Clear separation of Domain, Application, and Infrastructure layers with strict dependency rules.

```
┌─────────────────────────────────────────────┐
│  Presentation Layer (GraphQL)               │
│  • Queries, Mutations, Resolvers            │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│  Application Layer (Use Cases)              │
│  • Business workflows                       │
│  • Input/Output DTOs                        │
└──────────────────┬──────────────────────────┘
                   │
┌──────────────────▼──────────────────────────┐
│  Domain Layer (Business Logic)              │
│  • Entities, Value Objects                  │
│  • Repository Interfaces                    │
│  • Domain Events                            │
└──────────────────▲──────────────────────────┘
                   │
┌──────────────────┴──────────────────────────┐
│  Infrastructure Layer                       │
│  • Database (PostgreSQL)                    │
│  • Search (Elasticsearch)                   │
│  • Queue (Redis)                            │
│  • Cache (Redis)                            │
│  • Payment                                  │
└─────────────────────────────────────────────┘
```

### Project Structure

```
app/
├── Application/          # Use Cases (Feature-based)
│   ├── Auth/
│   ├── Order/
│   ├── Product/
│   └── User/
├── Domain/              # Core Business Logic
│   ├── Auth/
│   ├── Order/
│   ├── Product/
│   ├── User/
│   └── Shared/
├── Infrastructure/      # External Services
│   ├── Database/
│   ├── Search/
│   ├── Payment/
│   └── Providers/
└── Presentation/        # GraphQL Layer
    └── GraphQL/
        ├── Queries/
        └── Mutations/
```

---

## 🛠️ Tech Stack

### Backend
- **PHP 8.4** - Modern PHP with strict typing
- **Laravel 12** - Framework
- **PostgreSQL 15** - Primary database
- **Elasticsearch 9** - Search engine
- **Redis 7** - Cache & Queue

### API
- **GraphQL** - Laravel Lighthouse

### Authentication
- **OAuth2** - Laravel Passport

### Infrastructure
- **Docker** - Container orchestration
- **Nginx** - Reverse proxy & web server
- **PHP-FPM** - PHP process manager

---

## 💾 Database Schema

### Core Tables

#### Users & Authentication
```sql
users                # User accounts
├── id
├── name
├── email
├── password
└── created_at

addresses            # Shipping/Billing addresses
├── id
├── user_id          → users.id
├── type             (shipping/billing)
├── address_line_1
├── city
├── country
└── postal_code
```

#### Products & Inventory
```sql
products             # Product catalog
├── id
├── name
├── description
├── category
├── brand
├── price
├── stock
└── created_at

stock_reservations   # Inventory management
├── id
├── product_id       → products.id
├── order_id         → orders.id
├── quantity
├── status           (pending/confirmed/cancelled)
└── expires_at
```

#### Orders & Payments
```sql
orders               # Customer orders
├── id
├── user_id          → users.id
├── order_number     (unique)
├── status           (pending/processing/completed/cancelled)
├── payment_status   (pending/paid/failed/refunded)
├── total_amount
└── created_at

order_items          # Order line items
├── id
├── order_id         → orders.id
├── product_id       → products.id
├── quantity
├── price            (snapshot at order time)
└── line_total       (price × quantity)

payments             # Payment transactions
├── id
├── order_id         → orders.id
├── payment_method   (credit_card/iyzico/etc)
├── amount
├── status
├── transaction_id
└── created_at
```

---

## 🔍 Elasticsearch Integration

### Product Search Engine

**Purpose:** Fast full-text search with advanced filtering

**Features:**
- ✅ **Full-text search** - Search in product name and description
- ✅ **Turkish language support** - Turkish stopwords filtering
- ✅ **Multi-field search** - Name (boosted 3x) + Description
- ✅ **Advanced filters** - Category, brand, price range, stock status
- ✅ **Real-time sync** - Auto-indexing on product CRUD operations
- ✅ **Bulk indexing** - Fast initial indexing (500 products in ~100ms)

### Index Mapping

```json
{
  "settings": {
    "analysis": {
      "analyzer": {
        "turkish": {
          "type": "standard",
          "stopwords": "_turkish_"
        }
      }
    }
  },
  "mappings": {
    "properties": {
      "name": { "type": "text", "analyzer": "turkish" },
      "description": { "type": "text", "analyzer": "turkish" },
      "category": { "type": "keyword" },
      "brand": { "type": "keyword" },
      "price": { "type": "float" },
      "stock": { "type": "integer" }
    }
  }
}
```

### Synchronization Strategy

#### Automatic Sync (Real-time)

Product CRUD operations automatically update Elasticsearch:

```php
// Product created → Index document
$this->elasticsearchClient->indexDocument($product->id, $data);

// Product updated → Re-index document
$this->elasticsearchClient->indexDocument($product->id, $data);

// Product deleted → Delete document
$this->elasticsearchClient->deleteDocument($product->id);
```

#### Manual Sync (Bulk)

```bash
# Full reindex (recreate index + index all products)
make elasticsearch-index

# Or manually:
docker-compose exec app php artisan elasticsearch:index --recreate

# Reindex without recreating
docker-compose exec app php artisan elasticsearch:index
```

### Search Query Example

```graphql
query {
  products(
    filter: {
      query: "laptop"           # Full-text: name^3, description
      category: "Electronics"   # Exact match
      brand: "Apple"            # Exact match
      minPrice: 500             # Range: gte
      maxPrice: 2000            # Range: lte
      inStock: true             # Range: stock > 0
    }
    limit: 20
    offset: 0
  ) {
    id
    name
    price
    stock
  }
}
```

### Commands

```bash
# Start Elasticsearch
docker-compose up -d elasticsearch

# Create index and index products
make elasticsearch-index

# Check Elasticsearch status
curl http://localhost:9200

# Check product index
curl http://localhost:9200/products/_search?pretty

# Reindex products
docker-compose exec app php artisan elasticsearch:index --recreate
```

---

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Make (optional)

### Installation

```bash
# 1. Clone repository
git clone <repository-url>
cd ecommerce

# 2. Copy environment file
cp .env.example .env

# 3. Run setup (all-in-one)
make setup
```

---

## 📝 License

MIT License
