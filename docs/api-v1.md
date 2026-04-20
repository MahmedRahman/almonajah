# API V1

Base URL: `/api/v1`

## Response Contract

- Success shape:
  - `success`: boolean
  - `data`: payload object
  - `meta.pagination`: present on paginated endpoints
- Query params:
  - `page`
  - `per_page` (1..100)
  - `q` (search)
  - `category_name` (where supported)
  - `year` (where supported)
  - `type` (`audio` on search endpoints)

## Endpoints

- `GET /videos` (main video home feed; `GET /home` is an alias with the same behaviour)
- `POST /auth/login` (returns bearer token)
- `POST /auth/google` (Google Sign-In: send `id_token` **or** OAuth `access_token`; returns bearer token)
- `POST /auth/forgot-password` (sends reset email when the account exists)
- `POST /auth/reset-password` (email + token + new password; same token as in the email link)
- `GET /auth/me` (auth required)
- `POST /auth/logout` (auth required)
- `GET /categories`
- `GET /search`
- `GET /search/suggestions`
- `GET /assets/{asset}`
- `GET /assets/{asset}/related`
- `GET /assets/{asset}/comments`
- `GET /audio/home`
- `GET /audio/tracks`
- `GET /audio/tracks/{assetId}`
- `GET /playlists`
- `GET /playlists/{playlist}`
- `GET /scholars`
- `GET /scholars/{scholar}`
- `GET /shorts`
- `GET /live/feed`

## Notes

- Protected endpoints require `Authorization: Bearer <token>`.
