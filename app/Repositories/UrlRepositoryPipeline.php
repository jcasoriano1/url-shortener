<?php

namespace App\Repositories;

class UrlRepositoryPipeline implements UrlRepositoryInterface
{
    private array $repositories;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        SwooleUrlRepository $swooleRepository,
        RedisUrlRepository $redisRepository,
        EloquentUrlRepository $eloquentRepository,
    ) {
        $this->repositories = [
            $swooleRepository,
            $redisRepository,
            $eloquentRepository,
        ];
    }

    public function encode(string $url): ?string
    {
        return $this->pipeline('encode', $url);
    }

    public function decode(string $code): ?string
    {
        return $this->pipeline('decode', $code);
    }

    public function save(string $url, string $code, ?array $repositories = null): void
    {
        if (is_null($repositories)) {
            $repositories = array_reverse($this->repositories);
        }

        foreach ($repositories as $repository) {
            $repository->save($url, $code);
        }
    }

    /**
     * Run the given method through the pipeline of repositories
     */
    private function pipeline(string $method, string $value): ?string
    {
        $missed = [];

        foreach ($this->repositories as $repository) {
            if ($result = $repository->{$method}($value)) {
                // if result is found, save it on the missed repositories
                match ($method) {
                    'encode' => $this->save($value, $result, $missed),
                    'decode' => $this->save($result, $value, $missed),
                };

                return $result;
            }

            // save a cache miss so we can save the result if found
            $missed[] = $repository;
        }

        return null;
    }
}
