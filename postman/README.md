# Postman Collection - E-Commerce GraphQL API

Complete Postman collection for testing the E-Commerce GraphQL API.

## 📦 Contents

- **E-Commerce.postman_collection.json** - Full API collection with all queries and mutations
- **Local.postman_environment.json** - Local development environment variables

---

## 🚀 Quick Start

### 1. Import Collection

**In Postman:**
1. Click **Import** button
2. Select `E-Commerce.postman_collection.json`
3. Collection will appear in left sidebar

### 2. Import Environment

1. Click **Environments** (left sidebar)
2. Click **Import**
3. Select `Local.postman_environment.json`
4. Select **Local Development** from environment dropdown (top right)

### 3. Start Testing

1. **Register/Login** to get access token (automatically saved to environment)
2. Use authenticated endpoints with Bearer token
3. Test all features!

### 4. If URL is Empty (Manual Fix)

If the URL field appears empty after import:

1. **Click on any request**
2. **In URL field, type:** `{{base_url}}/graphql`
3. **Or manually type:** `http://localhost:8080/graphql`
4. **Save the request** (Ctrl+S / Cmd+S)

**Note:** Make sure "Local Development" environment is selected (top right dropdown)

---

## 📋 Collection Structure

### 1. **Auth** (2 requests)
- ✅ Register - Create new user account
- ✅ Login - Authenticate existing user

**Auto-save token:** Both requests automatically save `access_token` to environment

### 2. **User** (2 requests)
- ✅ Get My Profile - Get authenticated user info
- ✅ Update Profile - Update name and email

**Authentication:** Required (Bearer token)

### 3. **Address** (5 requests)
- ✅ Get My Addresses - List all user addresses
- ✅ Create Address - Create shipping/billing address
- ✅ Update Address - Update existing address
- ✅ Set Default Address - Mark address as default
- ✅ Delete Address - Remove address

**Authentication:** Required (Bearer token)

### 4. **Product** (6 requests)
- ✅ Get All Products - List products with pagination
- ✅ Search Products (Elasticsearch) - Full-text search with filters
- ✅ Get Single Product - Get product by ID
- ✅ Create Product (Admin) - Create new product
- ✅ Update Product (Admin) - Update existing product
- ✅ Delete Product (Admin) - Remove product

**Authentication:** 
- Public: Get All, Search, Get Single
- Admin only: Create, Update, Delete

**Note:** `created_at` and `updated_at` are nullable for Elasticsearch results (timestamp data not indexed)

### 5. **Order** (2 requests)
- ✅ Get My Orders - List user's orders with items
- ✅ Create Order (Buy Now) - Create order and process payment

**Authentication:** Required (Bearer token)

---

## 🔑 Environment Variables

### Local Development

| Variable | Value | Description |
|----------|-------|-------------|
| `base_url` | `http://localhost:8080` | API base URL |
| `access_token` | Auto-saved | JWT authentication token |
| `token_type` | `Bearer` | Token type |

### How to Use

Variables are referenced using `{{variable_name}}`:
- **URL:** `{{base_url}}/graphql`
- **Authorization:** `Bearer {{access_token}}`

---

## 🔐 Authentication Flow

### Step 1: Register or Login

**Register:**
```graphql
mutation {
  register(input: {
    name: "John Doe"
    email: "john@example.com"
    password: "password123"
  }) {
    access_token
    user { id name email }
  }
}
```

**Login:**
```graphql
mutation {
  login(input: {
    email: "john@example.com"
    password: "password123"
  }) {
    access_token
    user { id name email }
  }
}
```

### Step 2: Token Auto-Saved

Both requests have a **Test Script** that automatically saves `access_token` to environment:

```javascript
if (pm.response.code === 200) {
  const res = pm.response.json();
  if (res.data?.login) {
    pm.environment.set('access_token', res.data.login.access_token);
  }
}
```

### Step 3: Use Authenticated Endpoints

All protected endpoints use **Bearer Token** auth with `{{access_token}}` variable.

---

## 📝 Request Examples

### Get My Orders

**Query:**
```graphql
query {
  myOrders {
    id
    order_number
    status
    payment_status
    total_amount
    created_at
    items {
      id
      product_id
      product_name
      price
      quantity
      line_total
    }
  }
}
```

**Response:**
```json
{
  "data": {
    "myOrders": [
      {
        "id": "1",
        "order_number": "ORD-20241017-001",
        "status": "completed",
        "payment_status": "paid",
        "total_amount": 2499.99,
        "created_at": "2024-10-17 01:30:00",
        "items": [
          {
            "id": "1",
            "product_id": "1",
            "product_name": "MacBook Pro 16\"",
            "price": 2499.99,
            "quantity": 1,
            "line_total": 2499.99
          }
        ]
      }
    ]
  }
}
```

### Search Products (Elasticsearch)

**Query:**
```graphql
query {
  products(filter: {
    query: "laptop"
    category: "Electronics"
    brand: "Apple"
    minPrice: 500
    maxPrice: 3000
    inStock: true
  }) {
    id
    name
    category
    brand
    price
    stock
  }
}
```

**Features:**
- Full-text search in name and description
- Turkish language support
- Name boosted 3x for relevance
- Price range filtering
- Stock availability
- Performance: ~100-150ms

---

## 🎯 Testing Workflow

### Complete Test Flow

1. **Register** → Get access token
2. **Get My Profile** → Verify authentication
3. **Create Address** → Add shipping address
4. **Get All Products** → Browse catalog
5. **Search Products** → Test Elasticsearch
6. **Create Order** → Place order with payment
7. **Get My Orders** → Verify order created

### Admin Testing

1. Login with admin account
2. **Create Product** → Add new product to catalog
3. **Update Product** → Modify product details
4. **Delete Product** → Remove product

**Note:** Product CRUD operations automatically sync with Elasticsearch index.

---

## 🔍 GraphQL Schema

### Order Status Enum
- `pending` - Order created
- `processing` - Payment processing
- `completed` - Order completed
- `cancelled` - Order cancelled

### Payment Status Enum
- `pending` - Payment pending
- `paid` - Payment successful
- `failed` - Payment failed
- `refunded` - Payment refunded

### Payment Method Enum
- `IYZICO` - Iyzico payment gateway

### Address Type
- `shipping` - Shipping address
- `billing` - Billing address

---

## 🚨 Common Issues

### 1. Authentication Error

**Error:** `"message": "Unauthenticated."`

**Solution:**
- Make sure you've logged in/registered
- Check `{{access_token}}` is set in environment
- Verify Bearer token is configured in request

### 2. GraphQL Syntax Error

**Error:** `"message": "Syntax Error..."`

**Solution:**
- Check query syntax
- Ensure all required variables are provided
- Verify field names match schema

### 3. DateTime Format Error

**Error:** `"Expected a value of type DateTime..."`

**Solution:**
- DateTime format must be: `Y-m-d H:i:s`
- Example: `"2024-10-17 01:30:00"`

### 4. Admin Permission Error

**Error:** `"message": "Unauthorized"`

**Solution:**
- Admin-only endpoints require `role: admin`
- Login with admin account
- Regular users cannot create/update/delete products

---

## 📚 Additional Resources

- **GraphQL Schema:** `/graphql/schema.graphql`
- **API Documentation:** `README.md`
- **Disaster Recovery:** `DISASTER_RECOVERY.md`
- **Elasticsearch Docs:** `ELASTICSEARCH.md`

---

## 🎯 Subscription Feature (Future)

**Note:** WebSocket subscriptions are not yet implemented.

**Planned Features:**
- Real-time order status updates
- Payment status notifications
- Stock updates

**Requirements:**
- Pusher/Soketi configuration
- Laravel Broadcasting setup
- WebSocket server

**Example (future):**
```graphql
subscription {
  orderStatusUpdated(orderId: "1") {
    orderId
    status
    paymentStatus
    updatedAt
  }
}
```

---

## 📝 License

MIT License

---

## 🆘 Support

For issues or questions:
1. Check README.md
2. Review GraphQL schema
3. Test with Postman Console
4. Check application logs: `docker-compose logs app`
