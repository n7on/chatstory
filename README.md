# ChatStory WordPress Plugin

Tell your company story through recorded team chat conversations. Create characters and chat messages to showcase your team dynamics.

## Features

- **Character Management**: Create employee profiles with names, roles, avatars, and character traits
- **Chat Creation**: Build chat conversations with multiple messages
- **Manual & Import Options**: Add messages manually or import from JSON
- **Live Playback Mode**: Messages play back in real-time with typing indicators and configurable delays
- **Playback Controls**: Start/pause/resume with adjustable speed (0.5x to 2x)
- **Beautiful Frontend Display**: Animated, responsive chat interface that looks like a real chat recording
- **Shortcode Support**: Easily embed chats anywhere using `[chatstory id="1"]`
- **Translation Ready**: Full i18n support

## Installation

1. Upload the `chatstory` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The database tables will be created automatically on activation

## Usage

### 1. Create Characters

1. Go to **ChatStory > Characters** in WordPress admin
2. Click **Add New Character**
3. Fill in the details:
   - **Name**: Employee name (required)
   - **Role**: Job title (e.g., CEO, Developer)
   - **Avatar URL**: Link to profile picture
   - **Character Traits**: Description of their communication style
4. Click **Save Character**

### 2. Create a Chat

1. Go to **ChatStory > Chats**
2. Click **Add New Chat**
3. Enter:
   - **Title**: Chat conversation title
   - **Description**: Brief description (optional)
4. Click **Save Chat**

### 3. Add Messages

1. After saving a chat, the messages section appears
2. Click **Add Message**
3. Fill in:
   - **Character**: Select who is speaking
   - **Message**: The chat message text
   - **Timestamp**: Time display (e.g., "10:30 AM")
   - **Order**: Message sequence number (auto-increments)
   - **Delay (seconds)**: How long to wait before the next message (default: 2)
4. Click **Save Message**
5. Repeat to build the conversation

### 4. Preview Your Chat

Before publishing, you can preview your chat in the context of your theme:

1. Click the **Preview** button next to any chat in the list, OR
2. Click **Preview Chat** button while editing a chat
3. The chat opens in a new tab showing exactly how visitors will see it
4. Preview includes your theme's header, footer, and sidebar

### 5. Display Chat on Frontend

Copy the shortcode shown in the chat editor (e.g., `[chatstory id="1"]`) and paste it into any post or page.

The chat will display with playback controls:
- **Start Chat** button to begin playback
- **Pause/Resume** to control playback
- **Speed selector** to adjust playback speed (0.5x, 1x, 1.5x, 2x)
- Messages appear one by one with typing indicators
- Automatic scrolling to show new messages

## JSON Import

Import complete chats with characters and messages:

1. Go to **ChatStory > Chats**
2. Click **Import from JSON**
3. Paste your JSON data
4. Click **Import**

### JSON Format

See `sample-chat.json` and `sample-character.json` for a complete example. Basic structure:

## MCP Integration (AI Access)

ChatStory includes built-in support for the Model Context Protocol (MCP), allowing AI assistants like Claude to manage your chats and characters programmatically.

### Setup

1. **Install Dependencies** (if not already done):
   ```bash
   composer install
   ```

2. **Configure Your AI Assistant**:

   **For Claude Desktop** (or other MCP clients), add to your configuration:

   **Via HTTP:**
   ```json
   {
     "mcpServers": {
       "chatstory": {
         "command": "npx",
         "args": ["-y", "@automattic/mcp-wordpress-remote", "http://your-site.com/wp-json/mcp/mcp-adapter-default-server"],
         "env": {
           "WP_USERNAME": "your-admin-username",
           "WP_APPLICATION_PASSWORD": "your-app-password"
         }
       }
     }
   }
   ```

   **Via WP-CLI:**
   ```json
   {
     "mcpServers": {
       "chatstory": {
         "command": "wp",
         "args": ["mcp-adapter", "serve", "--server=mcp-adapter-default-server", "--user=admin"],
         "cwd": "/path/to/wordpress"
       }
     }
   }
   ```

3. **Create WordPress Application Password**:
   - Go to WordPress Admin → Users → Your Profile
   - Scroll to "Application Passwords"
   - Create a new application password for MCP access

### Available MCP Tools

Once configured, AI assistants can use these tools:

**Characters:**
- `chatstory/list-characters` - List all characters
- `chatstory/get-character` - Get a specific character
- `chatstory/create-character` - Create a new character
- `chatstory/update-character` - Update a character
- `chatstory/delete-character` - Delete a character

**Chats:**
- `chatstory/list-chats` - List all chats
- `chatstory/get-chat` - Get a chat with all messages
- `chatstory/create-chat` - Create a new chat
- `chatstory/update-chat` - Update a chat
- `chatstory/delete-chat` - Delete a chat

**Messages:**
- `chatstory/list-messages` - List messages in a chat
- `chatstory/get-message` - Get a specific message
- `chatstory/create-message` - Create a new message
- `chatstory/update-message` - Update a message
- `chatstory/delete-message` - Delete a message

### Example Usage with AI

Once configured, you can ask your AI assistant:

- "List all ChatStory characters"
- "Create a new character named John Doe with role CEO"
- "Create a new chat titled 'Team Standup'"
- "Add a message to chat #1 from character #2 saying 'Hello team!'"
- "Show me all messages in chat #3"

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher
- Composer (for MCP integration)

## License
MIT

## Support

For issues or questions, please contact the plugin author.
