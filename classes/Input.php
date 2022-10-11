<?php

namespace Neat\Http\Server;

use InvalidArgumentException;
use Neat\Http\Server\Exception\FilterNotFoundException;
use RuntimeException;

class Input
{
    /** @var Request */
    protected $request;

    /** @var Session */
    protected $session;

    /** @var array */
    protected $data = [];

    /** @var array */
    protected $filters = [];

    /** @var string[] */
    protected $errors = [];

    /**
     * Input constructor
     *
     * @param Request $request
     * @param Session $session
     */
    public function __construct(Request $request, Session $session)
    {
        $this->request = $request;
        $this->session = $session;

        $this->init();
    }

    /**
     * Initialize the input
     */
    public function init()
    {
    }

    /**
     * @return Request
     */
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
     *
     * @param array $sources
     */
    public function load(...$sources)
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
    public function store()
    {
        $this->session->set(
            'input',
            [
                'data'   => $this->data,
                'errors' => $this->errors,
            ]
        );
    }

    /**
     * Set variable default
     *
     * @param string $var
     * @param mixed  $value
     */
    public function default(string $var, $value)
    {
        if (!isset($this->data[$var])) {
            $this->data[$var] = $value;
        }
    }

    /**
     * Get all variables
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Has variable?
     *
     * @param string $var
     * @return bool
     */
    public function has(string $var)
    {
        return array_key_exists($var, $this->data);
    }

    /**
     * Get variable
     *
     * @param string $var
     * @return mixed
     */
    public function get(string $var)
    {
        return $this->data[$var] ?? null;
    }

    /**
     * Set variable
     *
     * @param string $var
     * @param mixed  $value
     */
    public function set(string $var, $value)
    {
        $this->data[$var] = $value;
    }

    /**
     * Register custom input filter
     *
     * @param string   $name
     * @param callable $filter
     */
    public function register(string $name, callable $filter)
    {
        $this->filters[$name] = $filter;
    }

    /**
     * Filter an input variable
     *
     * @param string            $var
     * @param null|string|array $filters
     * @param string            $type
     * @return mixed|null
     * @throws InvalidArgumentException
     */
    public function filter($var, $filters, $type = null)
    {
        $filters = $this->normalizeFilters($filters);

        $value = null;
        if (isset($this->data[$var])) {
            $value =&$this->data[$var];
        }
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
     * @return array
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
                "$method expects null, string or array as first argument '$type' given"
            );
        }
        if (is_string($filters)) {
            $filters = explode('|', $filters);
        }
        $normalized = [];
        foreach ($filters as $key => $filter) {
            if (is_string($key)) {
                $normalized[$key] = (array) $filter;
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
     *
     * @param string       $var
     * @param string|array $filters
     * @return bool|null
     */
    public function bool($var, $filters = null)
    {
        return $this->filter($var, $filters, 'bool');
    }

    /**
     * Get floating point input
     *
     * @param string       $var
     * @param string|array $filters
     * @return float|null
     */
    public function float($var, $filters = null)
    {
        return $this->filter($var, $filters, 'float');
    }

    /**
     * Get integer input
     *
     * @param string       $var
     * @param string|array $filters
     * @return int|null
     */
    public function int($var, $filters = null)
    {
        return $this->filter($var, $filters, 'int');
    }

    /**
     * Get string input
     *
     * @param string       $var
     * @param string|array $filters
     * @return string|null
     */
    public function string($var, $filters = null)
    {
        return $this->filter($var, $filters, 'string');
    }

    /**
     * Get file input
     *
     * @param string       $var
     * @param string|array $filters
     * @return Upload|null
     */
    public function file($var, $filters = null)
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
     *
     * @param string $field
     * @return string|null
     */
    public function error(string $field)
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Is valid?
     *
     * @param string $field (optional)
     * @return bool
     */
    public function valid(string $field = null): bool
    {
        if ($field) {
            return !isset($this->errors[$field]);
        }

        return empty($this->errors);
    }
}
