# Integration

Add a navigation link in Transport Documentation or the Client menu. Adjust the
relative path to match the installation folder:

```php
<a class="btn btn-outline-success"
   href="client_freight_invoice_module/index.php?clientid=<?=urlencode($clientid)?>&trip_id=<?=urlencode($tripId)?>">
    Create Freight Invoice
</a>
```

The query parameters are optional. When supplied, the module filters the client
and preselects the pending trip. The user may then select more eligible trips of
the same client.
