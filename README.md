# Savolix-debug-logger
A simple debug logging system for WordPress.

## Logging Messages
```php
SavolixDebugLogger::slix_debug_log('Message', 'level', 'source');
```


Logs a message with a severity level and an optional source identifier.

| Parameter | Type   | Default | Description                                                                 |
|-----------|--------|---------|-----------------------------------------------------------------------------|
| `$message`| string or array | —       | The log message. Arrays/objects will be converted to strings.              |
| `$level`  | string | `'info'` | Severity level: `debug`, `info`, `notice`, `warning`, `error`, `critical`. |
| `$source` | string | `''`     | Optional origin of the log (e.g. `'payment'`, `'api'`, etc.).              |
