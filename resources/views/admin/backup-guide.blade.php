@extends('layouts.admin')

@section('title', 'Backup Guide')

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Before Voting Starts</h2>
                <ul class="mb-0">
                    <li>Confirm `APP_DEBUG=false` in production.</li>
                    <li>Confirm admin accounts use strong passwords.</li>
                    <li>Export or copy the database before opening the election.</li>
                    <li>Test one sample voter on the same network.</li>
                    <li>Use a UPS or stable power source for the server computer.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Local Network Command</h2>
                <pre class="bg-light border rounded p-3 mb-3"><code>php artisan serve --host=0.0.0.0 --port=8000</code></pre>
                <p class="mb-0">Students should use the server computer IP address, for example `http://192.168.1.20:8000/voter/login`.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">SQLite Backup</h2>
                <p>For the local demo database, copy:</p>
                <pre class="bg-light border rounded p-3 mb-0"><code>database/database.sqlite</code></pre>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">MySQL Backup</h2>
                <pre class="bg-light border rounded p-3 mb-0"><code>mysqldump -u DB_USER -p DB_DATABASE &gt; election-backup.sql</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
