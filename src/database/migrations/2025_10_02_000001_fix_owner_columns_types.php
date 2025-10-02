<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert created_by/updated_by columns to text to accept numeric user IDs
        DB::statement("ALTER TABLE products ALTER COLUMN created_by TYPE text USING created_by::text;");
        DB::statement("ALTER TABLE products ALTER COLUMN updated_by TYPE text USING updated_by::text;");

        DB::statement("ALTER TABLE categories ALTER COLUMN created_by TYPE text USING created_by::text;");
        DB::statement("ALTER TABLE categories ALTER COLUMN updated_by TYPE text USING updated_by::text;");

        DB::statement("ALTER TABLE suppliers ALTER COLUMN created_by TYPE text USING created_by::text;");
        DB::statement("ALTER TABLE suppliers ALTER COLUMN updated_by TYPE text USING updated_by::text;");

        // change_logs.user_id also expects uuid; make it text so numeric ids work
        DB::statement("ALTER TABLE change_logs ALTER COLUMN user_id TYPE text USING user_id::text;");
    }

    public function down(): void
    {
        // best-effort: attempt to cast back to uuid (may fail if non-uuid data exists)
        DB::statement("ALTER TABLE products ALTER COLUMN created_by TYPE uuid USING (CASE WHEN created_by ~ '^[0-9a-fA-F-]{36}$' THEN created_by::uuid ELSE NULL END);");
        DB::statement("ALTER TABLE products ALTER COLUMN updated_by TYPE uuid USING (CASE WHEN updated_by ~ '^[0-9a-fA-F-]{36}$' THEN updated_by::uuid ELSE NULL END);");

        DB::statement("ALTER TABLE categories ALTER COLUMN created_by TYPE uuid USING (CASE WHEN created_by ~ '^[0-9a-fA-F-]{36}$' THEN created_by::uuid ELSE NULL END);");
        DB::statement("ALTER TABLE categories ALTER COLUMN updated_by TYPE uuid USING (CASE WHEN updated_by ~ '^[0-9a-fA-F-]{36}$' THEN updated_by::uuid ELSE NULL END);");

        DB::statement("ALTER TABLE suppliers ALTER COLUMN created_by TYPE uuid USING (CASE WHEN created_by ~ '^[0-9a-fA-F-]{36}$' THEN created_by::uuid ELSE NULL END);");
        DB::statement("ALTER TABLE suppliers ALTER COLUMN updated_by TYPE uuid USING (CASE WHEN updated_by ~ '^[0-9a-fA-F-]{36}$' THEN updated_by::uuid ELSE NULL END);");

        DB::statement("ALTER TABLE change_logs ALTER COLUMN user_id TYPE uuid USING (CASE WHEN user_id ~ '^[0-9a-fA-F-]{36}$' THEN user_id::uuid ELSE NULL END);");
    }
};
