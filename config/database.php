{
  "version": 2,
  "builds": [
    {
      "src": "/*.php",
      "use": "vercel-php@0.6.0"
    }
  ],
  "routes": [
    {
      "src": "/(.*)",
      "dest": "/index.php"
    }
  ],
  "env": {
    "APP_ENV": "production",
    "DB_HOST": "sql300.infinityfree.com",
    "DB_NAME": "if0_37858321_petCareDB",
    "DB_USER": "if0_37858321",
    "DB_PASS": "GcEQFqnydWQU"
  }
}