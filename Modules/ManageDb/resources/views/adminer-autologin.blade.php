<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Adminer</title>
</head>
<body style="margin:0; font: 14px/1.45 system-ui, sans-serif; background: #0f1324; color: #dbe4ff;">
<p style="padding: 1.25rem;">
    Connecting to Adminer. If you are not redirected, use the button below.
</p>
<form id="managedb-adminer-autologin" method="post" action="{{ $action }}">
@foreach($auth as $key => $value)
    <input type="hidden" name="auth[{{ $key }}]" value="{{ e($value) }}">
@endforeach
</form>
<p style="padding: 0 1.25rem 1.25rem;">
    <button type="submit" form="managedb-adminer-autologin" style="padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid rgba(140,177,255,0.45); background: #2a3d7a; color: #eef2ff; cursor: pointer;">Continue to Adminer</button>
</p>
<script>
document.getElementById('managedb-adminer-autologin').submit();
</script>
</body>
</html>
