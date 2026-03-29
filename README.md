# PIM Symfony Project

A Product Information Management (PIM) system built with **Symfony + API Platform + EAV architecture**.

This project demonstrates a scalable, API-first backend with dynamic attributes, a custom filtering DSL, and clean architecture principles.

---

## 🚀 Overview

This system provides a centralized platform for managing product data with a flexible schema.

Unlike traditional systems with fixed columns, this project uses a dynamic attribute model (EAV), allowing products to have different structures without database changes.

The system is designed with an **API-first approach**, where all business logic is exposed via REST endpoints.

---

## 🧬 Core Concept

Products are built using a dynamic attribute system:

- Custom attributes (color, size, material, etc.)
- Multiple data types (text, decimal, int, image)
- No schema changes required for new attributes
- Fully queryable via custom DSL filter

---

## 🧠 Data Model (EAV Pattern)

### Core Entities

- **Product** — base entity (id, sku)
- **ProductAttribute** — attribute definition (code, type)

### Value Storage (Type-based)

Values are stored in separate tables depending on type:

- ProductAttributeValueText
- ProductAttributeValueDecimal
- ProductAttributeValueInt
- ProductAttributeValueImage

---

## ⚡ Filtering (Custom DSL)

### Examples

```http
GET /api/products?filter=price GT 1000
GET /api/products?filter=sku BEGINS 'A'
GET /api/products?filter=price GT 1000 OR price LT 10
GET /api/products?filter=sku BEGINS 'A' AND price GT 1000
```

### Supported operators

- `EQ`
- `NE`
- `GT`, `GE`, `LT`, `LE`
- `BEGINS`
- `IN`

### Features

- Works with base fields (`sku`, `id`)
- Works with EAV attributes (`price`, `name`, etc.)
- Supports logical groups (`AND`, `OR`)
- AST-based parsing
- Converts DSL → Doctrine QueryBuilder

---

## 📡 API

### Endpoints

```http
GET    /api/products
GET    /api/products/{id}
POST   /api/products
PATCH  /api/products/{id}
DELETE /api/products/{id}
```

### Pagination

```http
GET /api/products?limit=20&offset=0
```

### Example Response

```json
{
  "items": [
    {
      "id": 1,
      "sku": "SKU-001",
      "attributes": {
        "name": "Apple",
        "price": 1200
      }
    }
  ],
  "total": 57,
  "limit": 20,
  "offset": 0
}
```

---

## 🧱 Architecture

```
┌───────────────────────┐
│     ApiResource       │  → API contract (DTO)
└──────────┬────────────┘
           │
┌──────────▼────────────┐
│        State          │  → business logic (Provider / Processor)
└──────────┬────────────┘
           │
┌──────────▼────────────┐
│       Service         │  → EAV + filtering logic
└──────────┬────────────┘
           │
┌──────────▼────────────┐
│       Entity          │  → Doctrine ORM
└───────────────────────┘
```

---

## 🔍 How Filtering Works (DSL → SQL)

```
Filter string (DSL)
        ↓
Parser
        ↓
AST (Abstract Syntax Tree)
        ↓
SmartEavFilterApplier
        ↓
Doctrine QueryBuilder
        ↓
SQL
```

### Example

DSL:

```
price GT 1000 AND sku BEGINS 'A'
```

Conceptual SQL:

```sql
WHERE price_value.value > 1000
AND product.sku LIKE 'A%'
```

---

## ✨ Features

- Dynamic EAV attributes
- Custom DSL filtering engine
- API Platform integration
- DTO-based API
- Pagination (limit/offset)
- Clean architecture
- Admin panel (EasyAdmin)

---

## ⚠️ Trade-offs

- Complex JOIN queries
- Requires indexing
- Potential N+1 issues

---

## 🔮 Roadmap

- Sorting (`?sort=price DESC`)
- Bulk attribute loading
- Attribute groups
- Caching layer
- Multi-tenant support

---

## 🧱 Tech Stack

- PHP 8+
- Symfony
- API Platform
- Doctrine ORM
- EasyAdmin
- Tailwind CSS

---

## ⚙️ Installation

```bash
composer install
```

---

## 🔐 Admin Setup

```bash
php bin/console app:create-admin email password
```

---

## 💬 Summary

This project demonstrates:

- EAV implementation
- DSL filtering engine
- API-first architecture
- Scalable backend design

---

## 📄 License

MIT
