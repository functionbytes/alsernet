# WebSocket Integration - LiveChat Widget

## Current Status: ✅ Frontend Ready, Backend Configuration Required

The LiveChat widget frontend is **fully prepared** for WebSocket/real-time messaging using Laravel Reverb. All the necessary code is in place and can be activated with simple configuration changes.

## 📋 What's Already Implemented

### Frontend Components

1. **Echo Configuration** (`echo.ts`)
   - Laravel Echo setup for Reverb
   - Auto-connection management
   - Disconnect handling

2. **WebSocket Hook** (`hooks/useWebSocket.ts`)
   - Real-time message listening
   - Typing indicators
   - Agent join notifications
   - Easy enable/disable toggle

3. **ConversationScreen Integration**
   - WebSocket hook integrated
   - Typing indicator support
   - Real-time message reception
   - Feature flag (`USE_WEBSOCKETS`)

## 🚀 How to Enable WebSockets

### Step 1: Configure Laravel Reverb

1. Update `.env`:
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=alsernet-app
REVERB_APP_KEY=alsernet-app-key
REVERB_APP_SECRET=your-secret-key
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

2. Update `.env` Vite variables:
```env
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Step 2: Start Laravel Reverb Server

```bash
php artisan reverb:start
```

Or run in background:
```bash
php artisan reverb:start --debug &
```

### Step 3: Enable WebSockets in Frontend

In `ConversationScreen.tsx`, change:
```tsx
const USE_WEBSOCKETS = false;  // Currently disabled
```

To:
```tsx
const USE_WEBSOCKETS = true;   // Enable real-time features
```

### Step 4: Rebuild Frontend

```bash
npm run build
# or
npm run dev
```

## 📡 Backend Events to Implement

Create these Laravel events to complete the integration:

### 1. MessageSent Event

```php
// app/Events/MessageSent.php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $conversationId,
        public array $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
```

### 2. AgentJoined Event

```php
// app/Events/AgentJoined.php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AgentJoined implements ShouldBroadcast
{
    public function __construct(
        public string $conversationId,
        public array $agent
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.joined';
    }
}
```

### 3. Dispatch Events in Controllers

```php
// When a message is sent
broadcast(new MessageSent($conversationId, [
    'id' => $message->id,
    'content' => $message->content,
    'author' => 'agent',
    'created_at' => $message->created_at,
]))->toOthers();

// When an agent joins
broadcast(new AgentJoined($conversationId, [
    'id' => $agent->id,
    'name' => $agent->name,
    'avatar' => $agent->avatar_url,
]));
```

## 🔐 Authorization Channels

Define authorization in `routes/channels.php`:

```php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user has access to this conversation
    return \App\Models\Conversation::where('id', $conversationId)
        ->where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhereHas('agents', function($q) use ($user) {
                      $q->where('id', $user->id);
                  });
        })
        ->exists();
});
```

## 🧪 Testing WebSocket Connection

1. Start Reverb:
```bash
php artisan reverb:start --debug
```

2. Open browser console in widget
3. Look for:
```
✅ Laravel Echo initialized for LiveChat Widget
🔌 Subscribing to channel: conversation.1
```

4. Send a test message from backend:
```bash
php artisan tinker
```

```php
broadcast(new \App\Events\MessageSent('1', [
    'id' => '123',
    'content' => 'Test message from backend',
    'author' => 'agent',
    'created_at' => now(),
]));
```

5. Check browser console:
```
📩 New message received: {...}
```

## 🎯 Features Ready to Use

Once enabled, these features will work automatically:

- ✅ **Real-time message delivery** - Messages appear instantly
- ✅ **Typing indicators** - See when agents are typing
- ✅ **Agent join notifications** - Know when an agent enters the chat
- ✅ **Connection status** - UI shows WebSocket connection state
- ✅ **Auto-reconnection** - Handles connection drops gracefully

## 📊 Monitoring

### Check Reverb Status
```bash
php artisan reverb:status
```

### View Active Connections
Check the Reverb console output when running with `--debug` flag

### Laravel Horizon (if using queues)
```bash
php artisan horizon
```
Then visit: http://your-domain.test/horizon

## 🐛 Troubleshooting

### WebSocket connection fails
- Check Reverb is running: `php artisan reverb:start`
- Verify .env variables are correct
- Check firewall allows port 8080
- Ensure VITE_ variables are set and frontend is rebuilt

### Messages not appearing
- Check browser console for errors
- Verify channel authorization in `routes/channels.php`
- Confirm events are dispatched with `->toOthers()` or without based on need
- Check Reverb debug output

### Typing indicators not working
- Ensure whisper events are enabled in channel
- Check browser console for whisper messages
- Verify no ad blockers are interfering

## 📝 Notes

- **Current Mode**: Mock data with setTimeout (PASO 1-5)
- **WebSocket Mode**: Set `USE_WEBSOCKETS = true` (PASO 6)
- **Transition**: Seamless - no UI changes needed
- **Performance**: WebSockets reduce server load vs polling
- **Scalability**: Reverb handles thousands of concurrent connections

## 🔄 Future Enhancements

Potential additions when needed:
- Read receipts (✓✓ seen by agent)
- Message delivery status tracking
- File upload with progress
- Audio/Video call initiation
- Screen sharing
- Co-browsing

---

**Status**: ✅ Ready to activate with backend configuration
**Frontend Work**: 100% Complete
**Backend Work**: Events + Channel Authorization needed
