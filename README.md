# PIM Symfony (EAV + API Platform)

A Product Information Management system built with **Symfony + API Platform** around a flexible **EAV** data model.

This project demonstrates a **scalable, API-first backend** with:

- dynamic attributes (EAV)
- custom filtering DSL
- field selection (`select`)
- multi-field sorting (`sort`)
- pagination
- DTO + Provider/Processor architecture
- image upload and file cleanup
- functional API test coverage
- read-optimized collection queries
- clean modular architecture

---

## 🚀 Overview

This system provides a centralized platform for managing product data with a flexible schema.

Unlike traditional systems with fixed columns, this project uses a dynamic attribute model (EAV), allowing products to have different structures without database changes.

The system is designed with an **API-first approach**, where business logic is exposed via REST endpoints and documented through OpenAPI.

A key focus of the project is not only correctness, but also **backend architecture under load**. The collection endpoint evolved from a classic ORM-based approach into a **read-optimized SQL/DBAL pipeline** designed for large EAV datasets.

The project also includes a growing **functional test suite** for both attribute and product APIs, covering request/response contracts, validation, query features, and persistence side effects.

---

## 🧬 Core Concept

Products are built using a dynamic attribute system:

- custom attributes (`color`, `size`, `material`, `price`, etc.)
- multiple data types (`text`, `decimal`, `int`, `image`)
- no schema changes required for new attributes
- fully queryable via filter, select, sort, and pagination

The external API representation is intentionally different from the internal persistence model:

- `id` stays at the root level
- `sku` is stored as a system field
- API responses expose `sku` inside `attributes`
- EAV values are stored in type-specific tables

This keeps the public contract consistent while still allowing an efficient storage model.

---

## 🧠 Filter DSL Design

Filtering is implemented as a custom **domain-specific language (DSL)**.

Architecture:

`string → tokens → AST → SQL`

This approach is inspired by expression parsing techniques described in the book *The C++ Programming Language* by Bjarne Stroustrup, where expressions are parsed with recursive descent and evaluated via a syntax tree.

This project uses recursive descent parsing and an AST-based execution model inspired by classic expression parser design.

Example:

```text
(price GT 1000 OR name EQ 'Phone') AND qty GE 1
```

### Components

- **Tokenizer**
    - converts the raw filter string into tokens

- **Parser**
    - builds an AST from tokens using recursive descent parsing

- **AST**
    - `ConditionNode` — a single condition
    - `GroupNode` — grouped logic (`AND` / `OR`)

- **FieldCollector**
    - extracts used fields from the AST for metadata resolution and validation

- **FilterFieldResolver**
    - resolves system fields vs EAV attributes

- **FilterSqlCompiler**
    - compiles AST into SQL fragments and parameters

- **FilterCompilerFacade**
    - connects tokenizer → parser → field collection → SQL compilation

### Supported Operators

- `EQ`
- `NE`
- `GT`
- `GE`
- `LT`
- `LE`
- `IN`
- `BEGINS`

### Supported Logic

- `AND`
- `OR`
- parentheses / nested groups

### Why this approach

- supports complex expressions
- separates parsing from execution
- works with both system fields and EAV attributes
- enables predictable `400` responses for invalid filters
- keeps the filter language independent from the transport layer

---

## 🧠 Data Model (EAV Pattern)

### Core Entities

- **Product** — base entity (`id`, `sku`, `created_at`, `updated_at`)
- **ProductAttribute** — attribute definition (`code`, `type`)

### Value Storage (Type-based)

Values are stored in separate tables depending on type:

- `ProductAttributeValueText`
- `ProductAttributeValueDecimal`
- `ProductAttributeValueInt`
- `ProductAttributeValueImage`

This keeps storage strongly typed while preserving EAV flexibility.

---

## ⚡ Query Capabilities

### Filtering (DSL)

```http
GET /api/products?filter=price GT 1000
GET /api/products?filter=name BEGINS 'A'
GET /api/products?filter=price GT 1000 OR price LT 10
GET /api/products?filter=(name EQ 'Alpha' OR name EQ 'Beta') AND qty GE 1
GET /api/products?filter=sku EQ 'SKU-001'
GET /api/products?filter=name IN ('One','Three')
```

Features:

- AST-based parsing
- works with system + EAV fields
- `AND` / `OR` / parentheses
- DSL is compiled into SQL
- EAV filtering is executed through `EXISTS` subqueries in the read model

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
- stable fallback sorting via `id DESC`
- EAV sorting is applied only in the ID query step

### Field Selection

```http
?select=sku,name,price
?select=price
?select=*
```

Rules:

- reduces payload size
- `id` stays at the root level
- selected fields are returned inside `attributes`
- collection endpoint respects explicit select without injecting default fields
- works with both system fields and EAV fields

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
POST   /api/products/{id}/images
```

---

## 📦 Example Response

### Collection Response

```json
{
  "items": [
    {
      "id": 1,
      "attributes": {
        "sku": "SKU-001",
        "name": "Phone",
        "price": 1200
      }
    }
  ],
  "total": 57,
  "limit": 20,
  "offset": 0
}
```

### Item Response

```json
{
  "id": 1,
  "attributes": {
    "sku": "SKU-001",
    "name": "Phone",
    "price": 1200,
    "qty": 5
  }
}
```

---

## 🧱 Architecture

```text
Request
  ↓
DTO / Input
  ↓
Processor / Provider
  ↓
Domain Services
  ↓
Doctrine / DBAL
  ↓
DTO / Output
  ↓
API Response
```

### Write Side

The write side keeps using **Doctrine ORM**:

- create / patch / delete
- domain entities
- processors/providers
- lifecycle hooks
- cleanup logic
- validation and persistence rules

### Read Side (Collection Endpoint)

The collection endpoint is implemented as a **read-optimized pipeline**.

```text
Request
  ↓
ProductCollectionContextFactory
  ↓
ProductCollectionQueryPlanner
  ↓
ProductCollectionQueryPlan
  ↓
ProductCountFetcher
  ↓
ProductIdsFetcher
  ↓
ProductBaseFieldsFetcher
  ↓
ProductAttributeValuesFetcher
  ↓
ProductCollectionAssembler
  ↓
API Response
```

### Read Model Strategy

The optimized collection flow is intentionally split into multiple steps:

1. **Count query**
    - counts total matching products

2. **ID query**
    - applies filter + sort + pagination
    - returns only product IDs for the requested page

3. **Base field loading**
    - loads system fields for selected product IDs

4. **Attribute value loading**
    - loads EAV values in batches grouped by type

5. **Assembly**
    - merges system fields + EAV attributes into the final API response

This avoids full ORM hydration for collection reads and keeps the endpoint predictable under heavy EAV workloads.

### DSL → SQL Flow

```text
Filter string
    ↓
Tokenizer
    ↓
Parser
    ↓
AST
    ↓
FieldCollector
    ↓
FilterSqlCompiler
    ↓
SQL fragments + parameters
    ↓
DBAL queries
```

---

## 🖼️ Image Upload & Cleanup

The project supports image uploads for image-type attributes.

### Upload Endpoint

```http
POST /api/products/{id}/images
```

Features:

- upload images for image-type attributes
- local storage strategy
- validation for attribute type
- product-aware file path organization

### Cleanup Behavior

Image files are removed when:

- a product is deleted
- an image value entity is removed

This keeps file storage consistent with database state.

### Attribute Deletion Protection

Attribute deletion is guarded when the attribute is already used by product values.

This prevents broken references and preserves EAV integrity.

---

## 🧩 Field System

Key services used to unify field behavior:

- **ProductSystemFieldRegistry** → system fields
- **AttributeMetadataProvider** → EAV metadata
- **AttributeTypeRegistry** → value entity/type mapping
- **ProductAttributeSelectionResolver** → resolves selected EAV attributes
- **ProductCollectionQueryPlanner** → builds normalized read plan

This keeps:

- validation
- documentation
- query planning
- runtime execution

consistent across the collection pipeline.

---

## ✅ Functional Testing

The project includes a dedicated **functional API testing foundation** based on `ApiTestCase`.

### Test Foundation

- isolated database cleanup before each test
- reusable JSON request helpers
- reusable API assertion helpers
- attribute/product test factory helpers
- dedicated `ProductApiTestCase`
- response contract assertions
- persistence-side assertions for product attributes

### Covered Product Test Areas

#### Create

- create product with required attributes
- create product with optional attributes
- fail when required attribute is missing
- fail when `sku` is missing
- fail when unknown attribute is provided

#### Get Item

- return product by id
- return optional attribute when present
- return `404` when product does not exist

#### Get Collection

- empty collection response
- collection with created products
- pagination behavior
- collection sorting behavior
- collection select behavior
- collection filtering behavior

#### Patch

- update existing attributes
- add optional attribute
- keep unchanged attributes intact
- remove optional attribute with `null`
- fail when required attribute becomes `null`
- fail on unknown attribute
- return `404` for missing product
- validate persisted DB state after patch

#### Filter DSL

Functional coverage includes:

- `EQ`
- `NE`
- `GT`
- `GE`
- `LT`
- `LE`
- `IN`
- `BEGINS`
- `AND`
- `OR`
- parentheses / nested groups
- system fields like `sku`
- invalid field / invalid syntax cases

#### Select

Collection-level select coverage includes:

- selecting only requested fields
- selecting system + EAV fields together
- selecting a single field
- combining select with pagination
- validation for unknown selected fields
- explicit select contract on collection responses

### Testing Philosophy

The functional test suite validates both:

- **response contract** — status codes, JSON shape, selected fields, query behavior
- **persistence side effects** — correct stored values in the product aggregate after create/patch

This is especially important for EAV systems, where a response can look correct while persistence logic is broken.

---

## ⚡ Performance Considerations

### Dataset

The project includes heavy test data generation for performance experiments:

- up to **100k products**
- around **100 attributes**
- dense EAV population

### Evolution of the Collection Endpoint

The collection endpoint started as an ORM-driven flow and was refactored into a DBAL-based read model.

This removed the main bottlenecks caused by:

- full ORM hydration of product collections
- large numbers of managed entities
- N+1-like collection traversal patterns
- expensive mapping through entity graphs for read-only responses

### Current Read Strategy

- DBAL-based collection read path
- count query separated from page ID query
- batched attribute loading by type
- AST → SQL filter compilation
- EAV sort joins limited to the ID query step
- no ORM-managed entities for collection responses

### Query Trade-offs

The optimized design still has expected trade-offs:

- complex SQL under heavy EAV filters
- expensive deep offset pagination on large datasets
- indexing strategy is required for production-like performance

### Next Performance Steps

- add targeted indexes for EAV value tables
- analyze heavy queries with `EXPLAIN`
- optimize `COUNT` strategy for very large filtered datasets
- consider seek/keyset pagination as an alternative to deep offset
- benchmark the read model on large generated datasets

---

## ✨ Features

- Dynamic EAV attributes
- Custom DSL filtering engine
- Select / Sort / Pagination
- DTO-based API
- Provider / Processor architecture
- DBAL read model for collection endpoint
- AST → SQL filter compilation
- Image upload and cleanup
- OpenAPI / Swagger docs
- EasyAdmin backend
- Functional API tests
- Translations support

---

## ⚠️ Trade-offs

- EAV adds SQL complexity
- collection reads require dedicated optimization
- deep offset pagination is expensive at scale
- indexing strategy is mandatory under large datasets
- more application code than fixed-schema CRUD

---

## 🔮 Roadmap

- targeted EAV indexing strategy
- `EXPLAIN`-driven SQL tuning
- alternative count/query strategies for large filters
- seek/keyset pagination for large page numbers
- attribute groups / families
- multi-tenant support
- search integration (Elastic/OpenSearch)
- large-scale benchmark dataset improvements
- more write-side validation scenarios

---

## 🧱 Tech Stack

- PHP 8+
- Symfony
- API Platform
- Doctrine ORM
- Doctrine DBAL
- MySQL
- EasyAdmin
- OpenAPI / Swagger
- PHPUnit

---

## ⚙️ Installation

```bash
composer install
```


## 🧪 Load Test Dataset

The project includes a dedicated command for generating a large dense EAV dataset for performance testing.

Example:

```bash
php bin/console app:generate:load-products-dbal 100000 --batch-size=100 --recreate-attributes --truncate-products
```

This command is useful for:

- generating a large benchmark catalog
- stress-testing filter / sort / pagination
- profiling the DBAL read model on realistic EAV volume
- validating indexing strategy and query plans

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
- DTO + Provider/Processor patterns
- functional API testing strategy
- write-model / read-model separation for collection performance
- scalable and extensible system design

---

## 📄 License

MIT
