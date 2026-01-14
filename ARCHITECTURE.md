# ChatStory Plugin Architecture

## Core Principles

This plugin uses a **functional, layered architecture** with PHP namespaces. NO object-oriented programming patterns (no classes, no singletons, no dependency injection). Everything is functions organized by namespace.

**Key Rule**: Separation of concerns through directory structure. Each directory has ONE clear purpose.

## Directory Structure & Responsibilities

```
chatstory/
├── chatstory.php          # Bootstrap ONLY - loads files, defines constants
│
├── hooks.php              # ROUTING TABLE - ALL WordPress hooks in one place
├── database.php           # Database schema definition & activation
├── assets.php             # Asset enqueuing (CSS/JS for admin + frontend)
├── pages.php              # Admin menu registration & page rendering
├── shortcodes.php         # Shortcode handlers
│
├── data/                  # BUSINESS LOGIC LAYER (namespace: ChatStory\Data)
│   ├── characters.php     # Character CRUD functions (NO WordPress coupling)
│   ├── chats.php          # Chat CRUD functions (NO WordPress coupling)
│   └── messages.php       # Message/reaction/typing/presence CRUD (NO WordPress coupling)
│
├── api/                   # EXTERNAL INTERFACE LAYER (namespace: ChatStory\Api)
│   ├── rest-api.php       # REST API endpoint registration (delegates to data/)
│   └── mcp-abilities.php  # MCP ability registration (delegates to data/)
│
├── views/                 # TEMPLATES ONLY (NO logic, just presentation)
│   ├── admin-chats.php
│   ├── admin-characters.php
│   ├── frontend-chat.php
│   └── preview-template.php
│
└── assets/                # STATIC FILES ONLY
    ├── css/               # Stylesheets
    └── js/                # JavaScript files
```

## Namespace Organization

| Namespace | Purpose | WordPress Coupling | Database Access |
|-----------|---------|-------------------|-----------------|
| `ChatStory` (root) | WordPress integration | YES | NO |
| `ChatStory\Data` | Business logic / CRUD | NO | YES (via $wpdb) |
| `ChatStory\Data\Messages` | Message-related CRUD | NO | YES (via $wpdb) |
| `ChatStory\Api` | External interfaces | YES (for registration) | NO (delegates to Data) |

## Data Flow

### Creating a Character via REST API
```
HTTP POST /wp-json/chatstory/v1/characters
    ↓
WordPress REST API routes request
    ↓
ChatStory\Api\create_character()          [api/rest-api.php]
    ↓
ChatStory\Data\create_character()         [data/characters.php]
    ↓
$wpdb->insert() to database
    ↓
Return character object
```

### Creating a Character via MCP
```
Claude calls chatstory/create-character ability
    ↓
WordPress Abilities API
    ↓
ChatStory\Api\register_mcp_abilities()    [api/mcp-abilities.php]
    ↓
ChatStory\Data\create_character()         [data/characters.php]
    ↓
$wpdb->insert() to database
    ↓
Return character object
```

### Rendering Admin Page
```
User navigates to /wp-admin/admin.php?page=chatstory-characters
    ↓
WordPress hook: admin_menu
    ↓
ChatStory\register_admin_menu()           [pages.php]
    ↓
ChatStory\render_characters_page()        [pages.php]
    ↓
Include views/admin-characters.php        [views/]
    ↓
JavaScript loads via ChatStory\enqueue_admin_assets() [assets.php]
    ↓
JavaScript calls REST API endpoints to fetch/save data
```

## Critical Rules for Code Modifications

### ✅ DO

1. **Add new CRUD functions to data/ files** - Business logic belongs here
2. **Register WordPress hooks in hooks.php** - ALL hooks in one place for visibility
3. **Delegate from API layer to data layer** - api/ should be thin wrappers
4. **Use namespaces** - Every function should have a namespace
5. **Return WP_Error for errors in data layer** - Consistent error handling
6. **Sanitize inputs in data layer** - Use sanitize_text_field(), sanitize_textarea_field()
7. **Keep views/ files simple** - Only HTML and minimal PHP for display

### ❌ DON'T

1. **DON'T put WordPress hooks in data/ files** - Data layer must be WordPress-agnostic
2. **DON'T put business logic in api/ files** - API layer only registers endpoints
3. **DON'T put business logic in views/** - Templates are presentation only
4. **DON'T create classes** - This is a functional codebase
5. **DON'T register hooks outside hooks.php** - Exception: bootstrap hooks in chatstory.php
6. **DON'T mix namespaces** - Each directory has its own namespace structure
7. **DON'T access $wpdb in api/ files** - Always delegate to data/ layer

## Function Naming Patterns

### Data Layer (data/*.php)
```php
namespace ChatStory\Data;

function get_characters()           // Fetch all
function get_character($id)         // Fetch one
function create_character($data)    // Create new
function update_character($id, $data) // Update existing
function delete_character($id)      // Delete
```

### API Layer (api/*.php)
```php
namespace ChatStory\Api;

function register_routes()          // Register all REST routes
function get_characters()           // REST handler (delegates to Data layer)
function register_mcp_abilities()   // Register all MCP abilities
```

### WordPress Integration (root *.php)
```php
namespace ChatStory;

function register_admin_menu()      // WordPress hook callback
function enqueue_admin_assets()     // WordPress hook callback
function render_shortcode()         // WordPress hook callback
```

## Database Schema

### Tables
- `wp_chatstory_characters` - Character profiles (name, slug, avatar, role, traits)
- `wp_chatstory_chats` - Chat conversations (title, description)
- `wp_chatstory_events` - All events (messages, reactions, typing, presence)

### Event Types
Events table stores different event types using `event_type` column:
- `message` - Chat message (event_data contains: text, timestamp)
- `reaction` - Emoji reaction (event_data contains: reaction, target_event_id)
- `typing` - Typing indicator (event_data contains: duration, target_event_id)
- `presence` - Join/leave notification (event_data contains: action)

## REST API Endpoints

All endpoints prefixed with `/wp-json/chatstory/v1/`

### Characters
- `GET /characters` - List all
- `GET /characters/{id}` - Get one
- `POST /characters` - Create
- `PUT /characters/{id}` - Update
- `DELETE /characters/{id}` - Delete
- `POST /characters/import` - Bulk import

### Chats
- `GET /chats` - List all
- `GET /chats/{id}` - Get one with messages
- `POST /chats` - Create
- `PUT /chats/{id}` - Update
- `DELETE /chats/{id}` - Delete (cascade deletes messages)
- `GET /chats/{id}/preview-url` - Get preview URL
- `GET /chats/{id}/frontend` - Public endpoint (no auth required)

### Messages
- `GET /chats/{chat_id}/messages` - List messages for chat
- `POST /chats/{chat_id}/messages` - Create message
- `GET /messages/{id}` - Get one
- `PUT /messages/{id}` - Update
- `DELETE /messages/{id}` - Delete (cascade deletes reactions/typing)

### Reactions, Typing, Presence
Similar patterns - see api/rest-api.php for full list

## MCP Integration

### How MCP Works in This Plugin

1. **WordPress MCP Adapter** (composer package) - Automatically discovers abilities
2. **WordPress Abilities API** (composer package) - Provides `wp_register_ability()`
3. **Our MCP Abilities** (api/mcp-abilities.php) - Registers abilities that delegate to data layer

### MCP Ability Pattern
```php
wp_register_ability('chatstory/ability-name', [
    'label' => __('Human Readable Name', 'chatstory'),
    'description' => __('What this ability does', 'chatstory'),
    'input_schema' => [ /* JSON schema for inputs */ ],
    'output_schema' => [ /* JSON schema for outputs */ ],
    'execute_callback' => function($input) {
        // Delegate to data layer
        return ChatStory\Data\some_function($input);
    },
    'permission_callback' => function() {
        return current_user_can('manage_options');
    },
]);
```

### Available MCP Abilities
- `chatstory/list-characters`, `chatstory/get-character`, `chatstory/create-character`, etc.
- `chatstory/list-chats`, `chatstory/get-chat`, `chatstory/create-chat`, etc.
- `chatstory/list-messages`, `chatstory/get-message`, `chatstory/create-message`, etc.

All abilities require `manage_options` permission.

## Adding New Features

### Example: Adding a "Tags" Feature

1. **Create data layer** (`data/tags.php`):
```php
<?php
namespace ChatStory\Data;

function get_tags() { /* ... */ }
function create_tag($data) { /* ... */ }
// etc.
```

2. **Register REST endpoints** (`api/rest-api.php`):
```php
register_rest_route($namespace, '/tags', [
    'methods' => 'GET',
    'callback' => __NAMESPACE__ . '\\get_tags',
    // ...
]);
```

3. **Register MCP abilities** (`api/mcp-abilities.php`):
```php
wp_register_ability('chatstory/list-tags', [
    'execute_callback' => function() {
        return Data\get_tags();
    },
]);
```

4. **Add hooks** (`hooks.php`):
```php
// If needed for admin UI, shortcodes, etc.
```

5. **Update database** (`database.php`):
```php
// Add table creation SQL if needed
```

## Error Handling

### Data Layer
Return `WP_Error` on failure:
```php
if (empty($data['name'])) {
    return new \WP_Error('missing_name', 'Name is required');
}
```

### API Layer
Check for `WP_Error` and return as-is:
```php
$result = Data\create_character($data);
if (is_wp_error($result)) {
    return $result; // WordPress handles WP_Error in REST API
}
return rest_ensure_response($result);
```

## Security

### Input Sanitization
Always sanitize in data layer:
- `sanitize_text_field()` - Single line text
- `sanitize_textarea_field()` - Multi-line text
- `sanitize_title()` - Slugs
- `intval()` - Integers
- `floatval()` - Decimals

### Permission Checks
- REST API: `check_permission()` callback uses `current_user_can('manage_options')`
- MCP: `permission_callback` uses `current_user_can('manage_options')`
- Frontend: Public endpoint (`chatstory/v1/chats/{id}/frontend`) uses `__return_true`

### Nonce Verification
- Admin AJAX: REST API nonce (`wp_rest`) verified by WordPress
- Frontend: No nonce needed for public endpoints
- Preview URLs: Custom nonce (`chatstory_preview_{id}`) verified in `handle_preview()`

## Testing Strategy

### Unit Testing (Recommended)
- Test data/ functions independently (mock $wpdb)
- Test api/ functions by verifying they call correct data/ functions
- No need to test WordPress hooks directly

### Integration Testing
- Test REST endpoints with actual WordPress installation
- Test MCP abilities through MCP adapter
- Test admin UI manually or with browser automation

## Common Modifications

### Q: How do I add a new field to characters?
1. Update `database.php` - Add column to schema
2. Update `data/characters.php` - Add field to create/update functions
3. Update `api/rest-api.php` - No changes needed (auto-handles new fields)
4. Update `api/mcp-abilities.php` - Add field to input/output schemas
5. Update views/ templates - Add field to forms

### Q: How do I add a new admin page?
1. Update `pages.php` - Add menu registration and render function
2. Create new view in `views/` - Add template file
3. Update `hooks.php` - Add `add_submenu_page()` call

### Q: How do I add a new shortcode?
1. Update `shortcodes.php` - Add shortcode handler function
2. Create new view in `views/` - Add template file
3. Update `hooks.php` - Add `add_shortcode()` call

## Bootstrap Order

The order of file loading in `chatstory.php` is important:

1. **Constants** - Define CHATSTORY_PLUGIN_FILE, CHATSTORY_PLUGIN_DIR
2. **Composer** - Load vendor/autoload.php
3. **MCP Adapter** - Initialize if available
4. **Core files** - database.php, assets.php, pages.php, shortcodes.php
5. **Data layer** - data/*.php (business logic)
6. **API layer** - api/*.php (interfaces)
7. **Hooks** - hooks.php (registers all WordPress hooks)
8. **Text domain** - Load translations

This order ensures dependencies are loaded before they're used.

## File Size Guidelines

- `chatstory.php` (bootstrap) - Should stay under 100 lines
- `hooks.php` (routing table) - Should list ALL hooks, stay readable
- `data/*.php` files - Can be large (business logic is complex)
- `api/*.php` files - Should be thin wrappers, mostly registration code
- `views/*.php` files - Should be mostly HTML with minimal PHP

## Debugging Tips

1. **Can't find where something is hooked?** - Look in `hooks.php`
2. **Business logic bug?** - Check `data/*.php` files
3. **API not responding?** - Check `api/rest-api.php` registration
4. **MCP ability not working?** - Check `api/mcp-abilities.php` registration
5. **Asset not loading?** - Check `assets.php` enqueue functions
6. **Admin page broken?** - Check `pages.php` and corresponding `views/*.php`

## Performance Considerations

- **Database queries** - All in data/ layer, easy to optimize
- **REST API** - Uses WordPress REST API caching
- **MCP** - Delegates to data layer (same performance as REST)
- **Asset loading** - Only loads on relevant pages (see `assets.php` conditions)

## Backwards Compatibility

If you need to maintain backwards compatibility:
- Keep old function names as aliases in same namespace
- Use `@deprecated` PHPDoc tag
- Redirect to new function implementation
- Remove after 2-3 major versions

Example:
```php
/**
 * @deprecated Use ChatStory\Data\get_characters() instead
 */
function chatstory_get_characters() {
    return ChatStory\Data\get_characters();
}
```
