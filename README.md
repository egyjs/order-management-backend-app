<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.1.0/github-markdown.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css">
<style>
.markdown-body {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
</style>
<div class="markdown-body">

# Laravel Order Management System
Senior Software Engineer PAY (Back-End) - Foodics

## Overview
This is a Laravel-based order management system designed to handle orders, manage product stock levels, and notify merchants when stock is low. The application uses service classes, request validation, and resource classes to ensure clean and maintainable code.

## Features
- **Order Creation**: Customers can place orders containing multiple products.
- **Stock Management**: The system checks and updates product stock levels after an order.
- **Low Stock Notification**: Sends email notifications when stock levels drop below a threshold.
- **Transactional Integrity**: Uses database transactions to ensure data consistency.
- **API Responses**: Standardized JSON responses for success, errors, and paginated data.

## Code Structure

### `App\Services\IngredientService`
Handles ingredient stock checks and low stock notifications.

### `App\Services\OrderService`
Processes orders and links products, ensuring that stock levels are updated.

### `App\Mixins\ResponseMixin`
Provides `success()`, `errors()`, and `paginate()` methods to standardize JSON responses.

### `App\Http\Requests\StoreOrderRequest`
Validates incoming requests to ensure they contain valid product IDs and quantities.

### `App\Http\Controllers\OrderController`
Handles HTTP requests related to orders and calls the `OrderService` to process orders.

### `App\Http\Resources\OrderResource`
Transforms the `Order` model into a standardized JSON structure for API responses.

## Installation

### Prerequisites
Ensure you have the following installed:
- PHP (>= 8.2)
- Composer
- SQLite or MySQL

### Steps
1. Clone the repository:
   ```bash
   git clone https://github.com/egyjs/order-management-backend-app.git 
   cd order-management-backend-app
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Create the `.env` file:
   ```bash
   cp .env.example .env
   ```
   Configure the `.env` file with your database and email settings.
   add `MERCHANT_EMAIL` to the `.env` file to set the email address to receive low-stock notifications.

4. Generate the application key:
   ```bash
   php artisan key:generate
   ```

5. Run migrations and seed the database:
   ```bash
   php artisan migrate --seed
   ```

6. Run the application:
   ```bash
   php artisan serve
   ```

## API Endpoints

### 1. **Create Order**
**Endpoint:** `POST /orders`

**Request Body:**
```json
{
    "products":[
        {
            "product_id":1,
            "qty":1
        }
    ]
}
```

**Response:**
- **Success (201):**
```json
{
    "success": true,
    "status": 201,
    "message": "Order placed successfully.",
    "data": {
        "id": 1,
        "created_at": "2025-01-15T01:57:18.000000Z",
        "products": [
            {
                "id": 1,
                "name": "Burger",
                "qty": 1
            }
        ]
    }
}
```

- **Error (422 - Validation Error):**
```json
{
    "success": false,
    "status": 422,
    "message": "The selected products.0.product_id is invalid.",
    "errors": {
        "products.0.product_id": [
            "The selected products.0.product_id is invalid."
        ]
    }
}
```

### 2. **Error Response Example:**
- **500 Internal Server Error:**
```json
{
  "success": false,
  "status": 500,
  "message": "An unexpected error occurred. Please try again later.",
  "errors": {}
}
```

## Configuration

### Database
Configure the database in the `.env` file:
for testing purposes, you can use the sqlite database
```env
DB_CONNECTION=sqlite
```

### Mail
Configure email settings for low-stock notifications:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME="Order System"
```

## Running Tests
To run tests, use:
```bash
php artisan test
```
Ensure that feature tests cover scenarios for order creation, stock management, and low-stock notifications.

output
```bash
❯ php artisan test

   PASS  Tests\Unit\Controllers\OrderControllerTest
  ✓ it creates an order successfully                                                                                                                      0.42s  
  ✓ it fails to create order due to exception                                                                                                             0.03s  

   PASS  Tests\Unit\Models\IngredientTest
  ✓ ingredient has product relationship                                                                                                                   0.04s  
  ✓ it sends low stock notification                                                                                                                       0.04s  
  ✓ low stock notification not sent if already notified                                                                                                   0.03s  

   PASS  Tests\Unit\Requests\StoreOrderRequestTest
  ✓ validation passes with valid data                                                                                                                     0.03s  
  ✓ validation fails with invalid data                                                                                                                    0.02s  
  ✓ validation fails when products are empty                                                                                                              0.02s  
  ✓ validation fails without products key                                                                                                                 0.02s  

   PASS  Tests\Unit\Services\IngredientServiceTest
  ✓ has sufficient stock returns true when stock is enough                                                                                                0.03s  
  ✓ has sufficient stock returns false when stock is insufficient                                                                                         0.03s  
  ✓ has stock lower than half returns true when stock is below half minimum                                                                               0.03s  
  ✓ has stock lower than half returns false when stock is above half minimum                                                                              0.02s  
  ✓ notify low stock sends notification when stock is low                                                                                                 0.03s  
  ✓ notify low stock does not send notification if already notified                                                                                       0.02s  
  ✓ update ingredients stock updates stock levels                                                                                                         0.13s  
  ✓ update ingredients stock throws insufficient stock exception                                                                                          0.03s  
  ✓ update ingredients stock sends low stock notification                                                                                                 0.03s  

   PASS  Tests\Unit\Services\OrderServiceTest
  ✓ process order successfully                                                                                                                            0.04s  
  ✓ process order throws product not found exception                                                                                                      0.03s  
  ✓ process order throws insufficient stock exception                                                                                                     0.03s  
  ✓ process order with multiple products                                                                                                                  0.10s  

   PASS  Tests\Feature\OrderFeatureTest
  ✓ order creation success                                                                                                                                0.04s  
  ✓ order creation fails for invalid product id                                                                                                           0.03s  
  ✓ order creation fails due to insufficient stock                                                                                                        0.05s  
  ✓ order creation fails for invalid quantity                                                                                                             0.03s  
  ✓ low stock notification is sent                                                                                                                        0.04s  
  ✓ multiple products order success                                                                                                                       0.11s  

  Tests:    28 passed (74 assertions)
  Duration: 1.68s

```
## CURL Commands
```bash
curl --location 'http://localhost:8000/api/orders' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--data '{
    "products":[
        {
            "product_id":1,
            "qty":1
        }
    ]
}'
```

## Troubleshooting
- **Migration Errors:** Ensure the database credentials in the `.env` file are correct.
- **Mail Errors:** Check email credentials and make sure your SMTP server allows connections.

## author
- Name: [Abdulrahman Elzahaby](https://www.linkedin.com/in/abdulrahman-el-zahaby)
- Email: [el3zahaby@gmail.com](mailto:el3zahaby@gmail.com)

</div>

