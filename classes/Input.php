<?php

namespace Neat\Http\Server;

use InvalidArgumentException;
use Neat\Http\Server\Exception\FilterNotFoundException;
use RuntimeException;

class Input
{
    protected Request $request;
    protected Session $session;
    protected array $data = [];
    /** @var array<string, callable> */
    protected array $filters = [];
    /** @var string[] */
    protected array $errors = [];

    public function __construct(Request $request, Session $session)
    {
        $this->request = $request;
        $this->session = $session;

        $this->init();
    }

    /**
     * Initialize the input
     */
    public function init() {}

    public function request(): Request
    {
        return $this->request;
    }

    /**
     * Clear the input
     */
    public function clear()
    {
        $this->data   = [];
        $this->errors = [];
    }

    /**
     * Load input from the requested sources and the session
     *
     * Flushes all previously applied filters and validations
     */
    public function load(string ...$sources): void
    {
        if (!$sources) {
            throw new RuntimeException('Input sources must not be empty');
        }

        $this->clear();
        foreach ($sources as $source) {
            if (!in_array($source, ['query', 'post', 'files', 'cookie'])) {
                throw new RuntimeException('Unknown input source: ' . $source);
            }
            $this->data = array_merge($this->data, $this->request->$source());
        }

        if ($input = $this->session->get('input')) {
            $this->session->unset('input');
            if (!$this->data) {
                $this->data   = $input['data'] ?? [];
                $this->errors = $input['errors'] ?? [];
            }
        }
    }

    /**
     * Store input to the session so the user can resume at the referring URL
     */
    public function store(): void
    {
        $this->session->set(
            'input',
            [
                'data'   => $this->data,
                'errors' => $this->errors,
            ],
        );
    }

    /**
     * Set variable default
     * @param mixed $value
     */
    public function default(string $var, $value): void
    {
        if (!isset($this->data[$var])) {
            $this->data[$var] = $value;
        }
    }

    /**
     * Get all variables
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Has variable?
     */
    public function has(string $var): bool
    {
        return array_key_exists($var, $this->data);
    }

    /**
     * Get variable
     * @return mixed
     */
    public function get(string $var)
    {
        return $this->data[$var] ?? null;
    }

    /**
     * Set variable
     * @param mixed $value
     */
    public function set(string $var, $value): void
    {
        $this->data[$var] = $value;
    }

    /**
     * Register custom input filter
     */
    public function register(string $name, callable $filter): void
    {
        $this->filters[$name] = $filter;
    }

    /**
     * Filter an input variable
     * @param null|string|array $filters
     * @return mixed|null
     * @throws InvalidArgumentException
     */
    public function filter(string $var, $filters, ?string $type = null)
    {
        $filters = $this->normalizeFilters($filters);

        if (!isset($this->data[$var])) {
            $this->data[$var] = null;
        }
        $value =& $this->data[$var];

        foreach ($filters as $filter => $params) {
            $filter = $this->getFilter($filter);
            if (!$this->applyFilter($filter, $var, $params)) {
                break;
            }
        }

        if ($type && $value !== null) {
            settype($value, $type);
        }

        return $value;
    }

    /**
     * @param null|string|array<string>|array<string, string> $filters
     * @throws InvalidArgumentException
     */
    private function normalizeFilters($filters): array
    {
        if (!$filters) {
            return [];
        }
        if (!is_string($filters) && !is_array($filters)) {
            $method = __METHOD__;
            $type   = gettype($filters);
            throw new InvalidArgumentException(
                "$method expects null, string or array as first argument '$type' given",
            );
        }
        if (is_string($filters)) {
            $filters = explode('|', $filters);
        }
        $normalized = [];
        foreach ($filters as $key => $filter) {
            if (is_string($key)) {
                $normalized[$key] = (array)$filter;
                continue;
            }
            $params              = explode(':', $filter);
            $filter              = is_string($key) ? $key : array_shift($params);
            $normalized[$filter] = $params;
        }

        return $normalized;
    }

    private function getFilter(string $filter): callable
    {
        if (isset($this->filters[$filter])) {
            return $this->filters[$filter];
        }
        if (function_exists($filter)) {
            return function (&$data, ...$params) use ($filter) {
                $data = $filter($data, ...$params);
            };
        }

        throw new FilterNotFoundException("Filter '$filter' is not a registered filter or global function");
    }

    private function applyFilter(callable $filter, string $var, array $params): bool
    {
        $error = $filter($this->data[$var], ...$params);
        if ($error) {
            $this->errors[$var] = is_array($error) ? current($error) : $error;

            return false;
        }

        return true;
    }

    /**
     * Get boolean input
     * @param string|array $filters
     */
    public function bool(string $var, $filters = null): ?bool
    {
        return $this->filter($var, $filters, 'bool');
    }

    /**
     * Get floating point input
     * @param string|array $filters
     */
    public function float(string $var, $filters = null): ?float
    {
        return $this->filter($var, $filters, 'float');
    }

    /**
     * Get integer input
     * @param string|array $filters
     */
    public function int(string $var, $filters = null): ?int
    {
        return $this->filter($var, $filters, 'int');
    }

    /**
     * Get string input
     * @param string|array $filters
     */
    public function string(string $var, $filters = null): ?string
    {
        return $this->filter($var, $filters, 'string');
    }

    /**
     * Get file input
     * @param string|array $filters
     */
    public function file(string $var, $filters = null): ?Upload
    {
        if ($this->get($var) instanceof Upload) {
            return $this->filter($var, $filters);
        }

        return null;
    }

    /**
     * Get errors
     *
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get error for a given field
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Is valid?
     */
    public function valid(?string $field = null): bool
    {
        if ($field) {
            return !isset($this->errors[$field]);
        }

        return empty($this->errors);
    }
}
