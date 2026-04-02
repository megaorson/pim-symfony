# PIM Symfony (EAV + API Platform)

A Product Information Management (PIM) system built with **Symfony + API Platform + EAV architecture**.

This project demonstrates a **scalable, API-first backend** with:

- dynamic attributes (EAV)
- custom filtering DSL
- field selection (`select`)
- multi-field sorting (`sort`)
- pagination
- clean modular architecture

---

## 🚀 Overview

This system provides a centralized platform for managing product data with a flexible schema.

Unlike traditional systems with fixed columns, this project uses a dynamic attribute model (EAV), allowing products to have different structures without database changes.

The system is designed with an **API-first approach**, where all business logic is exposed via REST endpoints and documented via OpenAPI.

---

## 🧬 Core Concept

Products are built using a dynamic attribute system:

- Custom attributes (color, size, material, price, etc.)
- Multiple data types (text, decimal, int, image)
- No schema changes required for new attributes
- Fully queryable via DSL filter, select and sort

---

## 🧠 Filter DSL Design (Parser Architecture)

The filtering system is implemented as a custom **domain-specific language (DSL)**.

The architecture follows a classic:

string → tokens → AST → execution

This approach is inspired by expression parsing techniques described in the book *The C++ Programming Language* by Bjarne Stroustrup (chapter about building a calculator).

In that example, mathematical expressions like:

2 + 3 * 5

are parsed using a recursive descent parser and evaluated via a syntax tree.

### In this project

The same idea is applied to filtering:

price > 100 AND sku ~ "test"

is processed as:

string → tokens → AST → Doctrine QueryBuilder

### Components

- **Tokenizer**
    - Converts string into tokens (IDENTIFIER, OPERATOR, VALUE, AND, OR, etc.)

- **Parser**
    - Builds an AST using recursive descent parsing

- **AST**
    - `ConditionNode` — single condition
    - `GroupNode` — logical grouping (AND / OR)

- **SmartEavFilterApplier**
    - Traverses AST and builds Doctrine QueryBuilder

### Example

Filter:

price > 100 AND sku ~ "test"

AST:

AND
├── Condition(price > 100)
└── Condition(sku BEGINS "test")

### Why this approach

- Supports complex expressions (AND / OR / nesting)
- Separates parsing from execution
- Extensible (new operators, functions)
- Enables clean validation and error handling (400 instead of 500)

---

## 🧠 Data Model (EAV Pattern)

### Core Entities

- **Product** — base entity (`id`, `sku`, `createdAt`, `updatedAt`)
- **ProductAttribute** — attribute definition (`code`, `type`)

### Value Storage (Type-based)

Values are stored in separate tables depending on type:

- ProductAttributeValueText
- ProductAttributeValueDecimal
- ProductAttributeValueInt
- ProductAttributeValueImage

---

## ⚡ Query Capabilities

### Filtering (DSL)

```http
GET /api/products?filter=price>1000
GET /api/products?filter=sku~'A'
GET /api/products?filter=price>1000 OR price<10
GET /api/products?filter=sku~'A' AND price>1000
```

Features:
- AST-based parsing
- Works with system + EAV fields
- AND / OR / parentheses
- Converts DSL → Doctrine QueryBuilder

---

### Sorting

```http
?sort=price
?sort=-price
?sort=price,sku
?sort=sku,-price
```

Rules:
- left-to-right priority
- first field = highest priority
- next fields = tie-breakers
- supports system + EAV fields
- stable sorting via fallback (`id DESC`)
- NULL-safe ordering

---

### Field Selection

```http
?select=id,sku,price
```

- reduces payload size
- `id` stays in root
- other fields go into `attributes`

---

### Pagination

```http
?page=1&limit=20
```

Internally converted to offset-based pagination.

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

---

## 📦 Example Response

```json
{
    "items": [
        {
            "id": 1,
            "attributes": {
                "sku": "SKU-001",
                "price": 1200
            }
        }
    ],
    "totalItems": 57,
    "limit": 20,
    "offset": 0
}
```

---

## 🧱 Architecture

```
Request
  ↓
ProductCollectionContextFactory
  ↓
ProductCollectionContext
  ↓
Collection Appliers
  ├── ProductFilterApplier
  ├── ProductSelectApplier
  └── ProductSortApplier
  ↓
Doctrine QueryBuilder
  ↓
ProductCollectionProvider
  ↓
ResultMapper
  ↓
API Response
```

---

## 🔍 DSL → SQL Flow

```
Filter string
    ↓
Tokenizer
    ↓
Parser
    ↓
AST
    ↓
SmartEavFilterApplier
    ↓
Doctrine QueryBuilder
    ↓
SQL
```

---

## 🧩 Field System (Key Architecture)

- **ProductSystemFieldRegistry** → system fields
- **AttributeMetadataProvider** → EAV metadata
- **ProductCollectionFieldProvider** → unified API field model

This eliminates duplication and keeps:

- validation
- documentation
- runtime logic

fully consistent.

---

## ⚡ Performance Considerations

Current strategy:
- DISTINCT + JOIN + ORDER BY
- hidden fields for sorting
- optimized select

Trade-offs:
- complex joins for EAV
- heavy queries under large datasets

Planned improvements:
- 2-step pagination (IDs → entities)
- metadata caching
- indexing strategy

---

## ✨ Features

- Dynamic EAV attributes
- Custom DSL filtering engine
- Select / Sort / Pagination
- API Platform integration
- DTO-based API
- OpenAPI / Swagger docs
- EasyAdmin backend
- Translations support

---

## ⚠️ Trade-offs

- Complex SQL queries
- Requires indexing strategy
- EAV complexity under high load

---

## 🔮 Roadmap

- Production-grade pagination (2-step)
- Attribute capabilities (filterable/sortable/selectable)
- Attribute groups / families
- Multi-tenant support
- Search integration (Elastic/OpenSearch)

---

## 🧱 Tech Stack

- PHP 8+
- Symfony
- API Platform
- Doctrine ORM
- MySQL
- EasyAdmin
- OpenAPI / Swagger

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

- advanced backend architecture
- EAV modeling
- custom query language (DSL)
- API-first design
- scalable and extensible system design

---

## 📄 License

MIT
