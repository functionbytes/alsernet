# Chat Widget

Complete React/TypeScript chat widget for Chat module.

## 📁 Structure

```
widget/
├── components/          # React UI components
│   ├── WidgetButton.tsx       # Floating button
│   ├── ChatWindow.tsx         # Main chat window
│   ├── PreChatForm.tsx        # Customer info form
│   ├── MessageList.tsx        # Message display
│   ├── MessageBubble.tsx      # Individual message
│   └── MessageInput.tsx       # Message input + file upload
├── hooks/              # Custom React hooks
│   └── useWidget.ts           # Main widget state management
├── services/           # Business logic services
│   ├── ApiService.ts          # HTTP API client
│   ├── StorageService.ts      # LocalStorage wrapper
│   └── WebSocketService.ts    # Laravel Echo/Reverb WebSocket
├── utils/              # Utility functions
│   ├── dateFormatter.ts       # Date/time formatting
│   └── fileValidator.ts       # File upload validation
├── types/              # TypeScript definitions
│   └── index.ts               # All type definitions
├── Widget.tsx          # Root component
└── main.tsx            # Entry point

widget-embed.ts         # Public embed script (loads widget)
```

## 🚀 Build & Development

### Install Dependencies

```bash
cd modules/Chat
npm install
```

### Development Mode

```bash
npm run widget:dev
```

### Production Build

```bash
npm run widget:build
```

Output: `public/build-chat/`

## 📝 Integration

### Option 1: Embed Script (Recommended)

Add this before `</body>` tag:

```html
<script>
  (function(w,d,s,o,f,js,fjs){
    w['ChatWidget']=o;w[o] = w[o] || function () { (w[o].q = w[o].q || []).push(arguments) };
    js = d.createElement(s), fjs = d.getElementsByTagName(s)[0];
    js.id = o; js.src = f; js.async = 1; fjs.parentNode.insertBefore(js, fjs);
  }(window, document, 'script', 'chatWidget', 'https://yoursite.com/build-chat/widget.js'));

  chatWidget('init', {
    websiteToken: 'YOUR_WEBSITE_TOKEN',
    baseUrl: 'https://yoursite.com',
    widgetColor: '#1f93ff',
    position: 'bottom-right'
  });
</script>
```

### Option 2: Direct Module Import

```typescript
import { Widget } from '@/widget/Widget';

<Widget config={{
  websiteToken: 'YOUR_TOKEN',
  baseUrl: 'https://api.yoursite.com',
  widgetColor: '#1f93ff',
  position: 'bottom-right'
}} />
```

## ⚙️ Configuration

```typescript
interface WidgetConfig {
    websiteToken: string;           // Required: Your website token
    baseUrl: string;                // Required: API base URL
    widgetColor?: string;           // Optional: Primary color (default: #1f93ff)
    position?: 'bottom-right' |     // Optional: Widget position
               'bottom-left';
    welcomeTitle?: string;          // Optional: Pre-chat form title
    welcomeTagline?: string;        // Optional: Pre-chat form subtitle
    locale?: string;                // Optional: Language (default: 'en')
}
```

## 🔌 API Endpoints Used

- `POST /api/widget/{token}/init` - Initialize conversation
- `POST /api/widget/{token}/messages` - Send message
- `GET /api/widget/{token}/messages/{id}` - Get messages
- `POST /api/widget/{token}/upload` - Upload file
- `PUT /api/widget/{token}/contact` - Update customer
- `POST /api/widget/{token}/csat` - Submit CSAT survey
- `GET /api/widget/{token}/availability` - Check agent availability

## 🌐 WebSocket Events

Listens to Laravel Reverb channel: `conversation.{id}`

Events:
- `NewMessageEvent` - New message received
- `ConversationAssignedEvent` - Agent assigned
- `ConversationStatusChangedEvent` - Status changed
- Whisper: `typing` - Typing indicators

## 📦 Dependencies

- **React 19** - UI framework
- **TypeScript 5.3** - Type safety
- **Vite 5** - Build tool
- **TailwindCSS 4** - Styling (via project config)
- **Font Awesome 6** - Icons (loaded via CDN)
- **Laravel Echo** - WebSocket client (loaded via CDN)
- **Pusher.js** - WebSocket transport (loaded via CDN)

## 🎨 Customization

### Colors

Set `widgetColor` in config to change primary color:

```javascript
chatWidget('init', {
  widgetColor: '#90bb13', // Your brand color
  // ...
});
```

### Position

```javascript
chatWidget('init', {
  position: 'bottom-left', // or 'bottom-right'
  // ...
});
```

### Pre-Chat Form

Customize welcome message:

```javascript
chatWidget('init', {
  welcomeTitle: 'Hola! 👋',
  welcomeTagline: '¿En qué podemos ayudarte?',
  // ...
});
```

## 🔒 Security

- All API requests use HTTPS in production
- File uploads validated (max 10MB, allowed types)
- XSS prevention via React's built-in escaping
- CSRF token validation on backend
- Rate limiting on API endpoints

## 🧪 Testing

```bash
# Run type check
npm run type-check

# Build for production (validates TypeScript)
npm run widget:build
```

## 📱 Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 📄 License

Part of Alsernet Chat module.
