# ERD Toko Silaturizky

The Laravel application stores orders in `transactions` and their lines in
`transaction_details`. The SQL bootstrap also exposes simple `orders` and
`order_items` views for tools that use the POS-oriented names.

```mermaid
erDiagram
    USERS ||--o{ TRANSACTIONS : places
    USERS ||--o{ TRANSACTIONS : handles
    USERS ||--o{ STOCK_LOGS : records
    CATEGORIES ||--o{ PRODUCTS : groups
    PRODUCTS ||--o{ TRANSACTION_DETAILS : sold
    PRODUCTS ||--o{ STOCK_LOGS : audited
    TRANSACTIONS ||--|{ TRANSACTION_DETAILS : contains
    TRANSACTIONS ||--o{ STOCK_LOGS : causes

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string role
        string wa_number
    }
    CATEGORIES {
        bigint id PK
        string name UK
        boolean is_active
    }
    PRODUCTS {
        bigint id PK
        bigint category_id FK
        string sku UK
        decimal purchase_price
        decimal selling_price
        decimal stock
    }
    TRANSACTIONS {
        bigint id PK
        bigint user_id FK
        bigint handled_by FK
        string transaction_number UK
        decimal total
        string status
        string sales_channel
    }
    TRANSACTION_DETAILS {
        bigint id PK
        bigint transaction_id FK
        bigint product_id FK
        decimal quantity
        decimal unit_price
        decimal purchase_price
    }
    STOCK_LOGS {
        bigint id PK
        bigint product_id FK
        bigint transaction_id FK
        bigint user_id FK
        decimal quantity_change
        decimal stock_before
        decimal stock_after
    }
```

Product stock is decimal to support fractional units. Product deletion is soft-delete
so historical order lines and stock audits retain their product reference.
