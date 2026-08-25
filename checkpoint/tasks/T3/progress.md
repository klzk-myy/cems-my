# T3 Progress — Fix #31: SetupController plain-text password

## §4 Verbatim commands / observations

- `grep admin_password /www/wwwroot/local.host/app/Http/Controllers/SetupController.php` → line 307: `'password' => $setupData['admin']['admin_password'],`
- `grep "use .*Hash" SetupController.php` → no match initially, but `read` at lines 22-23 confirmed `use Illuminate\Support\Facades\Hash;` already exists at line 23.
- `read` SetupController.php lines 303-313:
  ```php
  if (isset($setupData['admin'])) {
      $user = User::create([
          'username' => $setupData['admin']['admin_name'],
          'email' => $setupData['admin']['admin_email'],
          'password' => $setupData['admin']['admin_password'],
          'mfa_enabled' => false,
          'is_active' => true,
      ]);
  ```

## §5 Outcome

- **BLOCKED**: Checkpoint-writer subagent lacks edit/write permission on `/www/wwwroot/local.host/` (memory-tree only).
- Fix is trivial one-line edit: change `'password' => $setupData['admin']['admin_password']` to `'password' => Hash::make($setupData['admin']['admin_password'])` at line 307.
- Main agent must apply this edit when it resumes.
- No new imports needed (`Hash` facade already imported at line 23).