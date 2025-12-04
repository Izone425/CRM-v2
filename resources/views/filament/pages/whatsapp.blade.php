<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .whatsapp-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0; /* Remove padding for full screen */
            box-sizing: border-box;
        }

        .whatsapp-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 0; /* Remove border radius for full screen */
            box-shadow: none; /* Remove shadow */
            overflow: hidden;
            height: 100vh; /* Full viewport height */
            display: flex;
            flex-direction: column;
            border: none;
        }

        /* Header Filters */
        .filters-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 16px 24px; /* Reduced padding */
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            gap: 12px; /* Reduced gap */
            align-items: center;
            min-height: 80px; /* Fixed height */
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px; /* Reduced gap */
            background: white;
            border-radius: 12px; /* Smaller border radius */
            padding: 8px 12px; /* Reduced padding */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
        }

        .filter-group:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
        }

        .filter-checkbox {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 2px solid #e2e8f0;
            appearance: none;
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
        }

        .filter-checkbox:checked {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
        }

        .filter-checkbox:checked::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 10px;
            font-weight: 600;
        }

        .filter-select, .filter-input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: white;
            min-width: 150px; /* Reduced width */
            height: 32px; /* Fixed height */
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .filter-select {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: white;
            min-width: 150px;
            height: 32px;
            appearance: none !important;
            background-image: none !important;
        }

        /* Override any inherited select styling */
        .filter-select:not(.choices) {
            background-image: none !important;
        }

        /* Chat Layout */
        .chat-layout {
            display: flex;
            flex: 1;
            overflow: hidden;
            height: calc(100vh - 80px); /* Account for header */
        }

        /* Left Sidebar - Chat List */
        .chat-sidebar {
            width: 320px; /* Reduced width */
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-right: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
        }

        .chat-sidebar-header {
            padding: 16px; /* Reduced padding */
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            background: white;
            flex-shrink: 0;
        }

        .chat-sidebar-title {
            font-size: 18px; /* Reduced size */
            font-weight: 700;
            color: #1a202c;
            margin: 0;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .chat-list {
            flex: 1;
            overflow-y: auto;
            padding: 4px; /* Reduced padding */
        }

        .chat-item {
            background: white;
            border-radius: 12px;
            margin-bottom: 4px; /* Reduced margin */
            padding: 12px; /* Reduced padding */
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }

        .chat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .chat-item:hover::before {
            opacity: 1;
        }

        .chat-item:hover {
            transform: translateX(2px); /* Reduced movement */
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .chat-item.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }

        .chat-item.active::before {
            opacity: 0;
        }

        .chat-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .chat-name {
            font-weight: 600;
            font-size: 14px; /* Reduced size */
            color: #1a202c;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chat-item.active .chat-name {
            color: white;
        }

        .chat-meta {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .unread-indicator {
            width: 10px;
            height: 10px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 50%;
            box-shadow: 0 1px 4px rgba(255, 107, 107, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }

        .chat-timestamp {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        .chat-item.active .chat-timestamp {
            color: rgba(255, 255, 255, 0.8);
        }

        .chat-preview {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 12px;
        }

        .chat-item.active .chat-preview {
            color: rgba(255, 255, 255, 0.8);
        }

        .chat-direction-icon {
            font-size: 10px;
            opacity: 0.7;
        }

        /* Load More Button */
        .load-more-container {
            padding: 12px;
            text-align: center;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            background: white;
            flex-shrink: 0;
        }

        .load-more-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            font-size: 13px;
        }

        .load-more-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
        }

        /* Main Chat Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            min-width: 0; /* Allow flex shrinking */
        }

        .chat-header {
            background: white;
            padding: 16px 20px; /* Reduced padding */
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }

        .chat-header-title {
            font-size: 18px; /* Reduced size */
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }

        .messages-container {
            flex: 1;
            padding: 16px 20px; /* Reduced padding */
            overflow-y: auto;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            position: relative;
        }

        .messages-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(to bottom, rgba(248, 250, 252, 1), rgba(248, 250, 252, 0));
            pointer-events: none;
            z-index: 1;
        }

        .message-bubble {
            margin-bottom: 12px; /* Reduced margin */
            display: flex;
            animation: messageSlideIn 0.3s ease-out;
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-bubble.incoming {
            justify-content: flex-start;
        }

        .message-bubble.outgoing {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 65%; /* Reduced width */
            padding: 12px 16px; /* Reduced padding */
            border-radius: 16px;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(10px);
        }

        .message-content.incoming {
            background: white;
            border-bottom-left-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .message-content.outgoing {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .message-text {
            font-size: 14px; /* Reduced size */
            line-height: 1.4;
            margin: 0;
            word-wrap: break-word;
        }

        .message-timestamp {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 6px;
            text-align: right;
        }

        .quoted-message {
            background: rgba(0, 0, 0, 0.05);
            border-left: 3px solid #667eea;
            padding: 6px 10px;
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 12px;
            opacity: 0.8;
        }

        .message-content.outgoing .quoted-message {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: rgba(255, 255, 255, 0.5);
        }

        /* Media Messages */
        .message-media {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 6px;
            max-width: 240px; /* Reduced size */
        }

        .message-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .audio-player {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 240px; /* Reduced size */
        }

        .message-content.outgoing .audio-player {
            background: rgba(255, 255, 255, 0.1);
        }

        .audio-play-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .audio-play-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .audio-progress {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: rgba(0, 0, 0, 0.1);
            cursor: pointer;
            appearance: none;
            outline: none;
        }

        .audio-progress::-webkit-slider-thumb {
            appearance: none;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(102, 126, 234, 0.4);
        }

        .audio-time {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            min-width: 40px;
            text-align: right;
        }

        .message-content.outgoing .audio-time {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Message Input */
        .message-input-container {
            background: white;
            padding: 16px 20px; /* Reduced padding */
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 -1px 6px rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }

        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
            border-left: 3px solid rgba(255, 255, 255, 0.3);
            font-size: 13px;
        }

        .message-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .file-upload-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
        }

        .file-upload-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .message-textarea-container {
            flex: 1;
            position: relative;
        }

        .message-textarea {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 14px;
            resize: none;
            transition: all 0.2s ease;
            background: white;
            font-family: inherit;
            line-height: 1.4;
            max-height: 120px;
        }

        .message-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .send-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            height: 40px;
            flex-shrink: 0;
        }

        .send-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .send-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Right Sidebar - Contact Details */
        .contact-sidebar {
            width: 300px; /* Reduced width */
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-left: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
        }

        .contact-sidebar-header {
            padding: 16px; /* Reduced padding */
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            background: white;
            flex-shrink: 0;
        }

        .contact-sidebar-title {
            font-size: 16px; /* Reduced size */
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }

        .contact-details {
            padding: 16px; /* Reduced padding */
            display: flex;
            flex-direction: column;
            gap: 8px; /* Reduced gap */
            overflow-y: auto;
            flex: 1;
        }

        .contact-detail-card {
            background: white;
            border-radius: 12px;
            padding: 14px; /* Reduced padding */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .contact-detail-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .contact-detail-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }

        .contact-detail-icon {
            width: 32px; /* Reduced size */
            height: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            flex-shrink: 0;
        }

        .contact-detail-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .contact-detail-value {
            font-size: 14px; /* Reduced size */
            font-weight: 600;
            color: #1a202c;
            margin: 0;
            word-break: break-word;
        }

        .contact-detail-value a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .contact-detail-value a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .mark-read-btn {
            width: 100%;
            padding: 12px; /* Reduced padding */
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .mark-read-btn.read {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .mark-read-btn.unread {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .mark-read-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        /* Empty State */
        .empty-state {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }

        .empty-state-content {
            text-align: center;
            color: #64748b;
        }

        .empty-state-icon {
            font-size: 48px; /* Reduced size */
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state-title {
            font-size: 20px; /* Reduced size */
            font-weight: 600;
            margin-bottom: 6px;
        }

        .empty-state-description {
            font-size: 14px;
        }

        /* Loading States */
        .loading-spinner {
            display: inline-block;
            width: 14px; /* Reduced size */
            height: 14px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-left-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #64748b;
            font-weight: 500;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 1400px) {
            .contact-sidebar {
                width: 280px;
            }
            .chat-sidebar {
                width: 300px;
            }
        }

        @media (max-width: 1200px) {
            .contact-sidebar {
                width: 260px;
            }
            .chat-sidebar {
                width: 280px;
            }

            .filter-group {
                padding: 6px 10px;
            }

            .filter-select, .filter-input {
                min-width: 130px;
            }
        }

        @media (max-width: 768px) {
            .whatsapp-container {
                padding: 0;
            }

            .whatsapp-main {
                height: 100vh;
                border-radius: 0;
            }

            .filters-header {
                padding: 12px;
                flex-direction: column;
                align-items: stretch;
                min-height: auto;
            }

            .filter-group {
                width: 100%;
                justify-content: space-between;
            }

            .chat-sidebar, .contact-sidebar {
                display: none;
            }

            .chat-layout {
                flex-direction: column;
                height: calc(100vh - 60px);
            }
        }

        /* Scrollbar Styling */
        .chat-list::-webkit-scrollbar,
        .messages-container::-webkit-scrollbar,
        .contact-details::-webkit-scrollbar {
            width: 4px; /* Thinner scrollbar */
        }

        .chat-list::-webkit-scrollbar-track,
        .messages-container::-webkit-scrollbar-track,
        .contact-details::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 2px;
        }

        .chat-list::-webkit-scrollbar-thumb,
        .messages-container::-webkit-scrollbar-thumb,
        .contact-details::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .chat-list::-webkit-scrollbar-thumb:hover,
        .messages-container::-webkit-scrollbar-thumb:hover,
        .contact-details::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8, #6b46a3);
        }

        .filter-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
            transition: all 0.2s ease;
        }

        .filter-loading .loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-left-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Enhanced Filter Group with Loading States */
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            border-radius: 12px;
            padding: 8px 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            flex-shrink: 0;
            position: relative;
            min-height: 48px; /* Ensure consistent height */
        }

        .filter-group:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
        }

        /* Loading state styling */
        .filter-group:has(.filter-loading [wire\\:loading]) {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
            border-color: rgba(102, 126, 234, 0.2);
        }

        /* Enhanced filter elements */
        .filter-checkbox, .filter-select, .filter-input {
            transition: all 0.2s ease;
        }

        .filter-group:has(.filter-loading [wire\\:loading]) .filter-checkbox,
        .filter-group:has(.filter-loading [wire\\:loading]) .filter-select,
        .filter-group:has(.filter-loading [wire\\:loading]) .filter-input {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Enhanced Label Styling */
        .filter-label {
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            user-select: none;
            transition: color 0.2s ease;
        }

        .filter-group:has(.filter-loading [wire\\:loading]) .filter-label {
            color: #6b7280;
        }

        /* Loading Animation */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Pulse effect for loading states */
        .filter-loading .loading-spinner {
            animation: spin 1s linear infinite, pulse-glow 2s ease-in-out infinite alternate;
        }

        @keyframes pulse-glow {
            from {
                box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
            }
            to {
                box-shadow: 0 0 10px rgba(102, 126, 234, 0.6);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .filter-group {
                padding: 6px 10px;
                min-height: 44px;
            }

            .filter-loading .loading-spinner {
                width: 14px;
                height: 14px;
            }
        }

        @media (max-width: 768px) {
            .filter-group {
                width: 100%;
                justify-content: space-between;
                min-height: 48px;
            }

            .filter-loading {
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
            }
        }
    </style>

    <div class="whatsapp-container">
        <div class="whatsapp-main">
            <!-- Filters Header -->
            <div class="filters-header">
                <div class="filter-group">
                    <input type="checkbox" wire:model="filterUnreplied" class="filter-checkbox" id="unreplied-filter">
                    <label for="unreplied-filter" class="filter-label">Show Unreplied Only</label>
                    <div wire:loading wire:target="filterUnreplied" class="filter-loading">
                        <i class="loading-spinner"></i>
                    </div>
                </div>

                <div class="filter-group">
                    <i class="fas fa-user-tie" style="color: #667eea;"></i>
                    <select wire:model="selectedLeadOwner" class="filter-select">
                        <option value="">All Lead Owners</option>
                        @foreach(\App\Models\User::where('role_id', 1)->pluck('name', 'name') as $name => $nameLabel)
                            <option value="{{ $name }}">{{ $nameLabel }}</option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="selectedLeadOwner" class="filter-loading">
                        <i class="loading-spinner"></i>
                    </div>
                </div>

                <div class="filter-group">
                    <i class="fas fa-calendar" style="color: #667eea;"></i>
                    <input wire:model="startDate" type="date" class="filter-input" style="min-width: 140px;">
                    <span style="color: #64748b; font-weight: 500;">to</span>
                    <input wire:model="endDate" type="date" class="filter-input" style="min-width: 140px;">
                    <div wire:loading wire:target="startDate,endDate" class="filter-loading">
                        <i class="loading-spinner"></i>
                    </div>
                </div>

                <div class="filter-group">
                    <i class="fas fa-building" style="color: #667eea;"></i>
                    <input
                        type="text"
                        wire:model.debounce.500ms="searchCompany"
                        placeholder="Search company..."
                        class="filter-input"
                    />
                    <div wire:loading wire:target="searchCompany" class="filter-loading">
                        <i class="loading-spinner"></i>
                    </div>
                </div>

                <div class="filter-group">
                    <i class="fas fa-phone" style="color: #667eea;"></i>
                    <input
                        type="text"
                        wire:model.debounce.500ms="searchPhone"
                        placeholder="Search phone..."
                        class="filter-input"
                    />
                    <div wire:loading wire:target="searchPhone" class="filter-loading">
                        <i class="loading-spinner"></i>
                    </div>
                </div>
            </div>

            <!-- Main Chat Layout -->
            <div class="chat-layout">
                <!-- Left Sidebar - Chat List -->
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <h2 class="chat-sidebar-title">
                            <i class="fab fa-whatsapp" style="margin-right: 8px;"></i>
                            Chats
                        </h2>
                    </div>

                    <div class="chat-list" wire:poll.5s="fetchContacts">
                        @foreach($this->fetchContacts() as $contact)
                            @php
                                // Create a proper comparison for active state
                                $isActive = $selectedChat &&
                                        isset($selectedChat['user1']) &&
                                        isset($selectedChat['user2']) &&
                                        (($selectedChat['user1'] === $contact->user1 && $selectedChat['user2'] === $contact->user2) ||
                                            ($selectedChat['user1'] === $contact->user2 && $selectedChat['user2'] === $contact->user1));
                            @endphp

                            <div wire:click="selectChat('{{ $contact->user1 }}', '{{ $contact->user2 }}')"
                                class="chat-item {{ $isActive ? 'active' : '' }}">

                                <div class="chat-item-header">
                                    <div class="chat-name">
                                        {{ \Illuminate\Support\Str::limit($contact->participant_name, 15, '...') }}
                                    </div>

                                    <div class="chat-meta">
                                        @if($contact->is_from_customer && ($contact->is_read === null || $contact->is_read == false))
                                            <div class="unread-indicator"></div>
                                        @endif

                                        @if($contact->last_message_time)
                                            <div class="chat-timestamp">
                                                {{ \Carbon\Carbon::parse($contact->last_message_time)->format('M d, H:i') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="chat-preview">
                                    <i class="chat-direction-icon fa {{ $contact->is_from_customer ? 'fa-reply' : 'fa-share' }}"></i>
                                    <span>{{ \Illuminate\Support\Str::limit($contact->latest_message, 40, '...') }}</span>
                                </div>
                            </div>
                        @endforeach

                        @php $contacts = $this->fetchContacts(); @endphp
                        @if ($contacts->count() >= $contactsLimit)
                            <div class="load-more-container">
                                <button wire:click="loadMoreContacts" class="load-more-btn" wire:loading.attr="disabled">
                                    <span wire:loading wire:target="loadMoreContacts">
                                        <i class="loading-spinner" style="margin-right: 8px;"></i>
                                    </span>
                                    <span>Load More Chats</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Main Chat Area -->
                <div class="chat-main">
                    @if($selectedChat)
                        <!-- Chat Header -->
                        <div class="chat-header">
                            @php $details = $this->fetchParticipantDetails(); @endphp
                            <h2 class="chat-header-title">
                                <i class="fas fa-comments" style="margin-right: 12px; color: #667eea;"></i>
                                {{ $details['name'] }}
                            </h2>
                        </div>

                        <!-- Messages Container -->
                        <div class="messages-container">
                            <div wire:loading.remove wire:target="selectChat">
                                @foreach($this->fetchMessages($selectedChat) as $message)
                                    <div class="message-bubble {{ $message->is_from_customer ? 'incoming' : 'outgoing' }}">
                                        <div class="message-content {{ $message->is_from_customer ? 'incoming' : 'outgoing' }}">
                                            @if ($message->media_url)
                                                @if (str_contains($message->media_type, 'image'))
                                                    <div class="message-media">
                                                        <img
                                                            src="{{ $message->media_url }}"
                                                            alt="Image Message"
                                                            class="message-image"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                                                            onload="this.style.display='block'; if(this.nextElementSibling) this.nextElementSibling.style.display='none';"
                                                        >
                                                        <!-- Fallback content if image fails to load -->
                                                        <div style="display: none; padding: 12px; background: rgba(0,0,0,0.1); border-radius: 8px; text-align: center;">
                                                            <i class="fas fa-image" style="margin-bottom: 8px; font-size: 24px; opacity: 0.5;"></i>
                                                            <div style="font-size: 12px; opacity: 0.7;">Image not available</div>
                                                            <a href="{{ $message->media_url }}" target="_blank" style="color: inherit; text-decoration: underline; font-size: 11px;">
                                                                View Original
                                                            </a>
                                                        </div>
                                                    </div>
                                                @elseif (str_contains($message->media_type, 'audio'))
                                                    <div class="audio-player">
                                                        <audio id="audio-{{ $message->id }}" style="display: none;">
                                                            <source src="{{ $message->media_url }}" type="{{ $message->media_type }}">
                                                        </audio>

                                                        <button onclick="toggleAudio('audio-{{ $message->id }}', 'play-btn-{{ $message->id }}')"
                                                                id="play-btn-{{ $message->id }}" class="audio-play-btn">
                                                            <i class="fas fa-play"></i>
                                                        </button>

                                                        <input type="range" id="progress-{{ $message->id }}" value="0" step="0.1" class="audio-progress">
                                                        <span id="time-{{ $message->id }}" class="audio-time">00:00</span>
                                                    </div>
                                                @else
                                                    <a href="{{ $message->media_url }}" target="_blank" style="color: inherit; text-decoration: none;">
                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                            <i class="fas fa-file-alt"></i>
                                                            <span>Download File</span>
                                                        </div>
                                                    </a>
                                                @endif
                                            @else
                                                @if ($message->repliedMessage)
                                                    <div class="quoted-message">
                                                        {{ $message->repliedMessage->message }}
                                                    </div>
                                                @endif

                                                <div class="message-text">{!! nl2br(e($message->message)) !!}</div>
                                            @endif
                                            <div class="message-timestamp">
                                                {{ $message->created_at->format('M d, g:i A') }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div wire:loading wire:target="selectChat" class="loading-text">
                                <i class="loading-spinner" style="margin-right: 8px;"></i>
                                Loading messages...
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="message-input-container">
                            <form
                                x-data="{
                                    message: $wire.entangle('message').defer,
                                    send() {
                                        $wire.set('message', this.message).then(() => {
                                            $wire.sendMessage();
                                        });
                                    },
                                    clear() {
                                        this.message = '';
                                        this.$refs.textarea.style.height = 'auto';
                                    }
                                }"
                                x-init="window.addEventListener('messageSent', () => clear())"
                                @submit.prevent="send"
                            >
                                @if($showError)
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                                        {{ $errorMessage }}
                                    </div>
                                @endif

                                <div class="message-form">
                                    <label for="fileUpload" class="file-upload-btn">
                                        <i class="fas fa-paperclip"></i>
                                    </label>
                                    <input type="file" id="fileUpload" wire:model="file" style="display: none;">

                                    <div class="message-textarea-container">
                                        <textarea
                                            x-ref="textarea"
                                            x-model="message"
                                            @input="
                                                $refs.textarea.style.height = 'auto';
                                                $refs.textarea.style.height = $refs.textarea.scrollHeight + 'px';
                                            "
                                            class="message-textarea"
                                            rows="1"
                                            placeholder="Type a message..."
                                        ></textarea>
                                    </div>

                                    <button type="submit" class="send-btn" wire:loading.attr="disabled" wire:target="sendMessage">
                                        <span wire:loading wire:target="sendMessage">
                                            <i class="loading-spinner"></i>
                                        </span>
                                        <span wire:loading.remove wire:target="sendMessage">
                                            <i class="fas fa-paper-plane"></i>
                                            Send
                                        </span>
                                    </button>
                                </div>

                                @if ($file)
                                    <div style="margin-top: 12px; padding: 12px; background: #f1f5f9; border-radius: 8px; font-size: 14px; color: #64748b;">
                                        <i class="fas fa-file" style="margin-right: 8px;"></i>
                                        Uploading: {{ $file->getClientOriginalName() }}
                                    </div>
                                @endif
                            </form>
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="empty-state">
                            <div class="empty-state-content">
                                <div class="empty-state-icon">
                                    <i class="fab fa-whatsapp"></i>
                                </div>
                                <div class="empty-state-title">Select a Chat</div>
                                <div class="empty-state-description">Choose a conversation from the list to start messaging</div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Sidebar - Contact Details -->
                @if ($selectedChat)
                    <div class="contact-sidebar">
                        <div class="contact-sidebar-header">
                            <h2 class="contact-sidebar-title">Contact Details</h2>
                        </div>

                        @php $details = $this->fetchParticipantDetails(); @endphp

                        <div class="contact-details">
                            <button
                                wire:click="markMessagesAsRead({ 'user1': '{{ $selectedChat['user1'] }}', 'user2': '{{ $selectedChat['user2'] }}' })"
                                wire:loading.attr="disabled"
                                class="mark-read-btn"
                                x-data="{
                                    isRead: true,
                                    init() {
                                        this.checkReadState();
                                        Livewire.on('read-state-updated', (data) => {
                                            if (data.user1 === '{{ $selectedChat['user1'] }}' &&
                                                data.user2 === '{{ $selectedChat['user2'] }}') {
                                                this.isRead = data.isRead;
                                            }
                                        });
                                        this.$el.addEventListener('click', () => {
                                            setTimeout(() => {
                                                this.isRead = !this.isRead;
                                            }, 10);
                                        });
                                    },
                                    checkReadState() {
                                        @this.checkHasUnreadMessages('{{ $selectedChat['user1'] }}', '{{ $selectedChat['user2'] }}')
                                            .then(result => {
                                                this.isRead = !result;
                                            });
                                    }
                                }"
                                x-init="init()"
                                :class="isRead ? 'read' : 'unread'"
                            >
                                <span x-show="isRead">
                                    <i class="fas fa-check-double"></i>
                                    <span wire:loading.remove wire:target="markMessagesAsRead">Mark as Unread</span>
                                </span>
                                <span x-show="!isRead">
                                    <i class="fas fa-envelope"></i>
                                    <span wire:loading.remove wire:target="markMessagesAsRead">Mark as Read</span>
                                </span>
                                <span wire:loading wire:target="markMessagesAsRead">
                                    <i class="loading-spinner"></i>
                                    Updating...
                                </span>
                            </button>

                            <div class="contact-detail-card">
                                <div class="contact-detail-header">
                                    <div class="contact-detail-label">Lead Status</div>
                                </div>
                                <p class="contact-detail-value">{{ $details['lead_status'] }}</p>
                            </div>

                            <div class="contact-detail-card">
                                <div class="contact-detail-header">
                                    <div class="contact-detail-label">Name</div>
                                </div>
                                <p class="contact-detail-value" title="{{ $details['name'] }}">
                                    {{ \Illuminate\Support\Str::limit($details['name'], 25, '...') }}
                                </p>
                            </div>

                            <div class="contact-detail-card">
                                <div class="contact-detail-header">
                                    <div class="contact-detail-label">Email</div>
                                </div>
                                <p class="contact-detail-value">
                                    <a href="mailto:{{ $details['email'] }}" title="{{ $details['email'] }}">
                                        {{ \Illuminate\Support\Str::limit($details['email'], 28, '...') }}
                                    </a>
                                </p>
                            </div>

                            <div class="contact-detail-card">
                                <div class="contact-detail-header">
                                    <div class="contact-detail-label">Phone</div>
                                </div>
                                <p class="contact-detail-value">{{ $details['phone'] }}</p>
                            </div>

                            <div class="contact-detail-card">
                                <div class="contact-detail-header">
                                    <div class="contact-detail-label">Company</div>
                                </div>
                                @if ($details['company_url'])
                                    <p class="contact-detail-value">
                                        <a href="{{ $details['company_url'] }}" target="_blank">
                                            {{ $details['company'] }}
                                        </a>
                                    </p>
                                @else
                                    <p class="contact-detail-value">{{ $details['company'] }}</p>
                                @endif
                            </div>

                            <div class="contact-detail-card">
                                <div class="contact-detail-header">
                                    <div class="contact-detail-label">Source</div>
                                </div>
                                <p class="contact-detail-value">{{ $details['source'] }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- JavaScript for Audio Player -->
    <script>
        function toggleAudio(audioId, buttonId) {
            let audio = document.getElementById(audioId);
            let button = document.getElementById(buttonId);
            let progressBar = document.getElementById('progress-' + audioId.split('-')[1]);
            let timeDisplay = document.getElementById('time-' + audioId.split('-')[1]);

            if (audio.paused) {
                audio.play();
                button.innerHTML = '<i class="fas fa-pause"></i>';
            } else {
                audio.pause();
                button.innerHTML = '<i class="fas fa-play"></i>';
            }

            audio.ontimeupdate = function () {
                let currentTime = Math.floor(audio.currentTime);
                let minutes = Math.floor(currentTime / 60);
                let seconds = currentTime % 60;
                timeDisplay.innerText = minutes + ":" + (seconds < 10 ? '0' : '') + seconds;
                progressBar.value = (audio.currentTime / audio.duration) * 100;
            };

            progressBar.oninput = function () {
                audio.currentTime = (this.value / 100) * audio.duration;
            };

            audio.onended = function () {
                button.innerHTML = '<i class="fas fa-play"></i>';
                progressBar.value = 0;
                timeDisplay.innerText = '00:00';
            };
        }
    </script>
</x-filament::page>
