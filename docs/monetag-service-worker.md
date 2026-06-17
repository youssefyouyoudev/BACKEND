# Monetag service worker

The active Monetag service worker is served from:

- Local: `http://localhost:8000/sw.js`
- Production: `https://rifitv.com/sw.js`

The uploaded worker files contained multiple zone IDs:

- `sw (1).js`: `11162793`
- `sw (2).js`: `11133750`
- `sw (3).js`: `11137945`
- `sw (4).js`: `11162793`
- `sw.js`: `11133738`

`sw (3).js` was copied to `public/sw.js` because it is the only uploaded worker in the same `111379xx` Monetag account family as the configured zones `11137947`, `11137952`, `11137954`, and SmartLink `11137969`.

Deployment check:

```bash
curl -I https://rifitv.com/sw.js
```

The response should be `200` and use a JavaScript content type. Do not add more root service workers unless Monetag confirms a separate worker is required, because browsers allow only one active service worker scope per root path.
