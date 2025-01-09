# URL Shortener Service

Extremely low latency and scalable URL shortening service featuring multi-layer caching

## Setup Instructions

1. Clone the repository
2. Do `cp .env.example .env` and then update `APP_URL` to your local URL
3. Create new databases `url_shortener` and `url_shortener_test` and update the `.env` and `phpunit.xml` files with the correct database credentials
4. Run the following commands:

```bash
composer install
php artisan key:generate
php artisan migrate
```

## Unit Tests

You may run the tests with `php artisan test`

## Manual Testing

You may also test manually by importing the [Postman collection](URL%20Shortener.postman_collection.json) provided for your convenience.

## API Endpoints

### Encode URL

```http
POST /api/encode
Content-Type: application/json
{
    "url": "https://example.com/very/long/url"
}
```

Response:

```json
{
    "short_url": "http://app.test/s/AbC123d"
}
```

### Decode URL

```http
POST /api/decode
Content-Type: application/json
{
    "short_url": "http://app.test/s/AbC123d"
}
```

Response:

```json
{
    "original_url": "https://example.com/very/long/url"
}
```

### Short URL Redirect

Though not in the specs, I also added a `/s/{code}` route that will redirect to the original URL.

```http
GET /s/AbC123d
```

## Scalability

### Two Layers of Caching

For maximum performance and scalability, I added two layers of cache in front of the database.

1. **Swoole Table (L1 Cache)**
   - Supports extremely fast read and write speeds of up to 2 million operations per second, but is limited in number of rows cached depending on server memory
   
2. **Redis (L2 Cache)**
   - Can use Redis clusters for horizontal scaling
   - Usually responds in milliseconds

3. **Database (Persistent Storage)**
   - We use Eloquent to interact with the database so that we can swap out the database layer for a more specialized database if needed, with almost no code changes required
   - Indexes are strategically placed for O(log n) lookups for both encoding and decoding
   - In MySQL, since indexes have a maximum size, we hash the long URL and index that

In this manner, we can have extremely fast performance while keeping the database load manageable even at very high traffic, because of the two layers of caching in front of it.

When a cache miss occurs at the front of the pipeline, we backfill the cache with results from the back of the pipeline to keep the front layers warm with recent data.

### Octane

I've used Laravel Octane for maximum performance. This will allow us to reuse the framework so that it doesn't have to be bootstrapped on every request. 

### Deployment

After setting-up Octane on the server, it is also recommended to run `php artisan optimize` to cache routes, configs, event configurations, and more. 

## Security Considerations

- I chose 7-character unique codes, which gives us 62^7 = 3.5 trillion combinations
- Codes are generated randomly so they cannot be guessed or predicted
- I validated the URLs entered into the API
- I added in-app rate limiting to prevent abuse, although it is highly recommended to also add rate limiting at the network level

## Code Architecture

1. I created repositories for the Swoole cache, the Redis cache, and the database.
2. The database uses Eloquent so that we can swap out the database to a different driver if we outgrow the current one.
3. With this design, we can add more layers of caching in front of the database if needed - it just has to implement the `UrlRepositoryInterface` interface, and add it to the pipeline in the `UrlRepositoryPipeline` class.
4. The `UrlRepositoryPipeline` manages the pipeline of repositories, and the `UrlShortenerService` orchestrates the process of encoding and decoding URLs.
5. Validation is done in the `UrlDecodeRequest` and `UrlEncodeRequest` classes.
6. The `UrlShortenerController` handles the API endpoints - it uses the Request classes to validate the input, calls the Service to encode and decode URLs, and returns the appropriate response.

### Code Styles

1. The project follows the Laravel code style guide.
2. I ran `vendor/bin/pint` with all of the defaults, to be aligned with the rest of the ecosystem.
3. All method arguments are type-hinted and include return types for self-documenting code as well as defensive programming.
