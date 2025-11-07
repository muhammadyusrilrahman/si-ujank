# JSON API Endpoints for Vue Clients

This project now exposes lightweight JSON endpoints that mirror the data shown on
the Pegawai and Perhitungan TPP tables.  They are served from the web guard so the
existing session cookie is reused – no additional authentication work is required.

## Pegawai Listing

- **Route name**: `api.pegawais.index`
- **URL**: `/api/pegawais`
- **Query parameters**:
  - `search` – optional fuzzy match against nama/NIK/NIP/jabatan
  - `per_page` – 25, 50, or 100 (default 25)
  - `skpd_id` – optional SKPD filter (super admin only)
- **Response shape**:
  ```json
  {
    "data": [ { "id": 1, "fields": { ... }, "links": { "edit": "...", "destroy": "..." } } ],
    "filters": { "search": "", "per_page": 25 },
    "options": { "per_page": [25, 50, 100] },
    "permissions": { "can_manage": true },
    "meta": {
      "pagination": {
        "current_page": 1,
        "per_page": 25,
        "total": 120,
        "from": 1,
        "to": 25,
        "links": [ { "url": "...", "label": "&laquo; Previous", "active": false }, ... ]
      }
    }
  }
  ```

## TPP Calculations

- **Route name**: `api.tpp-calculations.index`
- **URL**: `/api/tpp-calculations`
- **Query parameters**:
  - `type` – opsi jenis ASN (`pns`, `pppk`, …)
  - `tahun`, `bulan` – required to activate listing
  - `per_page` – 25, 50, or 100 (default 25)
  - `search` – optional nama/NIP filter
- **Response shape**:
  ```json
  {
    "data": [ { "id": 1, "pegawai": { ... }, "extras": { ... }, "routes": { ... } } ],
    "summary": { "jumlah_tpp": 123456, ... },
    "filtersReady": true,
    "filters": { "type": "pns", "year": 2025, "month": 3, "per_page": 50, "search": "" },
    "options": {
      "types": [ { "value": "pns", "label": "PNS - Pegawai Negeri" }, ... ],
      "months": [ { "value": "1", "label": "Januari" }, ... ],
      "per_page": [25, 50, 100],
      "yearBounds": { "min": 2000, "max": 2030 }
    },
    "extras": { "order": ["plt20", ...], "labels": { "plt20": "TPP PLT 20%", ... } },
    "permissions": { "can_manage": true },
    "context": { "hidden_fields": { "jenis_asn": "pns", "tahun": 2025, "bulan": 3 } },
    "meta": { "pagination": { ... } }
  }
  ```

## Using the API from Vue

The table components still work with the existing SSR hydrated payload.  To switch a
page to use JSON fetches instead of full page reloads, pass the endpoint URL through
the Blade props (e.g. `api: { endpoint: route('api.pegawais.index') }`) and wire a
client-side call using `axios`.  Both payloads intentionally mirror the structures
that the Vue components already expect (`items`, `options`, `pagination`, etc.), so
the data can be dropped in without reshaping.

Refer to `resources/js/components/PegawaiIndexApp.vue` for the mapping that the API
produces.  Future work can progressively replace the current `window.location`
navigation with `axios.get(endpoint, { params })` calls and update the `items`,
`pagination`, and `filters` state in-place for a full SPA experience.
