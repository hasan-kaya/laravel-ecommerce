import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 30 },   // Ramp-up to 30 users
    { duration: '1m', target: 30 },    // Stay at 30 users for 1 min
    { duration: '20s', target: 0 },    // Ramp-down to 0
  ],
  thresholds: {
    http_req_duration: ['p(95)<5000'],  // 95% requests < 5s
    http_req_failed: ['rate<0.05'],     // Error rate < 5%
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const GRAPHQL_URL = `${BASE_URL}/graphql`;

export default function () {
  const timestamp = Date.now();
  const userId = `${timestamp}${__VU}${__ITER}`;

  // 1. Register
  let registerRes = http.post(GRAPHQL_URL, JSON.stringify({
    query: `mutation { register(input: {
      name: "User${userId}"
      email: "user${userId}@test.com"
      password: "password123"
    }) { access_token } }`
  }), {
    headers: { 'Content-Type': 'application/json' }
  });

  check(registerRes, { 'register ok': (r) => r.status === 200 });

  let token = null;
  try {
    token = JSON.parse(registerRes.body).data?.register?.access_token;
  } catch (e) {}

  if (!token) return;

  sleep(0.5);

  // 2. Get products
  let productsRes = http.post(GRAPHQL_URL, JSON.stringify({
    query: `query { products(limit: 5) { id stock } }`
  }), {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    }
  });

  check(productsRes, { 'products ok': (r) => r.status === 200 });

  let productId = null;
  try {
    const products = JSON.parse(productsRes.body).data?.products || [];
    const available = products.filter(p => p.stock > 0);
    if (available.length > 0) {
      productId = available[0].id;
    }
  } catch (e) {}

  if (!productId) return;

  sleep(0.5);

  // 3. Create order
  let orderRes = http.post(GRAPHQL_URL, JSON.stringify({
    query: `mutation { createOrder(input: {
      product_id: "${productId}"
      quantity: 1
      payment_method: IYZICO
    }) { id order_number status } }`
  }), {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    }
  });

  check(orderRes, { 'order created': (r) => r.status === 200 });

  sleep(0.5);
}
