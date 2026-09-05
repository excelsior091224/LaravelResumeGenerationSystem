# Resume Foundry Astro Workers prototype

This directory contains the low-cost SaaS migration prototype for the resume generation system.

## Stack

- Astro running on Cloudflare Workers
- Cloudflare D1 for reusable career data
- Cloudflare R2 for temporary or paid export files
- Optional Cloudflare Workers AI or external AI APIs for summaries and reviews
- Clerk is the first authentication candidate for MVP validation

## Commands

```bash
npm run dev
npm run build
npm run preview
npm run db:migrate:local
```

Before remote deployment, create the D1 database and R2 bucket in Cloudflare, then replace `REPLACE_WITH_CLOUDFLARE_D1_DATABASE_ID` in `wrangler.jsonc`.

## Migration principle

The existing Laravel app can remain as a one-shot resume generator while this app validates the saved career-log SaaS model. The MVP should store reusable career facts and generate resumes from those facts, rather than storing generated documents as the primary data source.
