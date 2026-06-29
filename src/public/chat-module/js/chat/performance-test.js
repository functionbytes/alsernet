/**
 * Chat Performance Testing Suite
 *
 * Usage:
 * 1. Open browser console
 * 2. Run: ChatPerformanceTest.runAll()
 * 3. View results in console
 */

(function() {
    'use strict';

    var perfResults = [];

    function generateMockMessage(id, type) {
        type = type || 'incoming';
        return {
            id: id,
            message_type: type,
            content: 'Test message ' + id + ' with some content that simulates a typical customer message. Lorem ipsum dolor sit amet.',
            created_at: new Date().toISOString(),
            sender: type === 'incoming' ? { name: 'Test Customer' } : { name: 'Agent Smith' },
            attachments: []
        };
    }

    function generateMockMessageWithAttachments(id) {
        var msg = generateMockMessage(id);
        msg.attachments = [
            {
                url: 'https://picsum.photos/200/150?random=' + id,
                file_name: 'test-image-' + id + '.jpg',
                mime_type: 'image/jpeg'
            },
            {
                url: 'https://example.com/file-' + id + '.pdf',
                file_name: 'document-' + id + '.pdf',
                mime_type: 'application/pdf'
            }
        ];
        return msg;
    }

    function benchmark(name, fn, iterations) {
        iterations = iterations || 1;
        var times = [];

        for (var i = 0; i < iterations; i++) {
            var start = performance.now();
            fn();
            var end = performance.now();
            times.push(end - start);
        }

        var avg = times.reduce(function(a, b) { return a + b; }) / times.length;
        var min = Math.min.apply(null, times);
        var max = Math.max.apply(null, times);

        var result = {
            test: name,
            iterations: iterations,
            avg: avg.toFixed(3),
            min: min.toFixed(3),
            max: max.toFixed(3),
            total: (avg * iterations).toFixed(3)
        };

        perfResults.push(result);
        console.log('[PERF] ' + name + ':', result);
        return result;
    }

    function testBuildMessageHtml() {
        console.group('Test: buildMessageHtml Performance');

        var msg = generateMockMessage(1);
        benchmark('Build simple incoming message', function() {
            window.ChatUtils.buildMessageHtml(msg);
        }, 100);

        var outgoingMsg = generateMockMessage(2, 'outgoing');
        benchmark('Build simple outgoing message', function() {
            window.ChatUtils.buildMessageHtml(outgoingMsg);
        }, 100);

        var msgWithAttachments = generateMockMessageWithAttachments(3);
        benchmark('Build message with 2 attachments', function() {
            window.ChatUtils.buildMessageHtml(msgWithAttachments);
        }, 100);

        console.groupEnd();
    }

    function testAppendMessageToChat() {
        console.group('Test: appendMessageToChat Performance');

        // Create test conversation container
        var testContainer = document.createElement('div');
        testContainer.className = 'chat-box-inner';
        testContainer.setAttribute('data-simplebar', '');
        testContainer.setAttribute('data-conversation-id', '999');

        var chatList = document.createElement('div');
        chatList.className = 'chat-list';
        chatList.setAttribute('data-conversation-id', '999');
        testContainer.appendChild(chatList);

        document.body.appendChild(testContainer);

        // Test single message append
        benchmark('Append single message', function() {
            var msg = generateMockMessage(Math.random() * 10000);
            window.ChatUtils.appendMessageToChat(msg, 999);
        }, 50);

        // Test burst of messages (simulates real-time flood)
        benchmark('Append 10 messages (burst)', function() {
            for (var i = 0; i < 10; i++) {
                var msg = generateMockMessage(Math.random() * 10000);
                window.ChatUtils.appendMessageToChat(msg, 999);
            }
        }, 10);

        // Cleanup
        setTimeout(function() {
            document.body.removeChild(testContainer);
        }, 500);

        console.groupEnd();
    }

    function testDeduplicationPerformance() {
        console.group('Test: Message Deduplication Performance');

        var messageIds = [];
        for (var i = 0; i < 1000; i++) {
            messageIds.push('msg_' + i);
        }

        // Test Set-based deduplication (optimized)
        var testSet = new Set();
        benchmark('Set.has() - 1000 lookups', function() {
            for (var i = 0; i < 1000; i++) {
                testSet.has(messageIds[i]);
            }
        }, 10);

        // Test DOM querySelector deduplication (old approach)
        var testDiv = document.createElement('div');
        for (var i = 0; i < 100; i++) {
            var child = document.createElement('div');
            child.setAttribute('data-message-id', messageIds[i]);
            testDiv.appendChild(child);
        }
        document.body.appendChild(testDiv);

        benchmark('querySelector() - 100 lookups', function() {
            for (var i = 0; i < 100; i++) {
                testDiv.querySelector('[data-message-id="' + messageIds[i] + '"]');
            }
        }, 10);

        document.body.removeChild(testDiv);

        console.groupEnd();
    }

    function testScrollPerformance() {
        console.group('Test: Scroll Performance');

        // Create test container
        var testContainer = document.createElement('div');
        testContainer.className = 'chat-box-inner';
        testContainer.setAttribute('data-simplebar', '');
        testContainer.setAttribute('data-conversation-id', '998');
        testContainer.style.cssText = 'height:300px;overflow-y:auto;';

        var chatList = document.createElement('div');
        chatList.className = 'chat-list';
        chatList.setAttribute('data-conversation-id', '998');

        // Add 50 messages to create scrollable content
        for (var i = 0; i < 50; i++) {
            var msgDiv = document.createElement('div');
            msgDiv.style.height = '80px';
            msgDiv.textContent = 'Message ' + i;
            chatList.appendChild(msgDiv);
        }

        testContainer.appendChild(chatList);
        document.body.appendChild(testContainer);

        benchmark('scrollChatToBottom (with RAF)', function() {
            window.ChatUtils.scrollChatToBottom(998);
        }, 50);

        // Cleanup
        setTimeout(function() {
            document.body.removeChild(testContainer);
        }, 500);

        console.groupEnd();
    }

    function testMemoryUsage() {
        console.group('Test: Memory Usage');

        if (window.performance && window.performance.memory) {
            var before = window.performance.memory.usedJSHeapSize;

            // Create 100 messages
            for (var i = 0; i < 100; i++) {
                var msg = generateMockMessageWithAttachments(i);
                window.ChatUtils.buildMessageHtml(msg);
            }

            var after = window.performance.memory.usedJSHeapSize;
            var increase = ((after - before) / 1024 / 1024).toFixed(2);

            console.log('[MEMORY] Used heap before: ' + (before / 1024 / 1024).toFixed(2) + ' MB');
            console.log('[MEMORY] Used heap after: ' + (after / 1024 / 1024).toFixed(2) + ' MB');
            console.log('[MEMORY] Increase: ' + increase + ' MB for 100 messages');

            perfResults.push({
                test: 'Memory usage (100 messages)',
                value: increase + ' MB'
            });
        } else {
            console.warn('[MEMORY] performance.memory not available (only in Chrome with --enable-precise-memory-info)');
        }

        console.groupEnd();
    }

    function runAll() {
        console.clear();
        console.log('%c=== Chat Performance Test Suite ===', 'font-size:16px;font-weight:bold;color:#90bb13');
        console.log('Testing message rendering, DOM operations, and memory usage\n');

        perfResults = [];

        testBuildMessageHtml();
        testAppendMessageToChat();
        testDeduplicationPerformance();
        testScrollPerformance();
        testMemoryUsage();

        console.log('\n%c=== Summary ===', 'font-size:14px;font-weight:bold;color:#13C672');
        console.table(perfResults);

        console.log('\n%cTarget Performance Benchmarks:', 'font-weight:bold');
        console.log('✅ Build message HTML: < 5ms average');
        console.log('✅ Append message: < 2ms average');
        console.log('✅ Deduplication (Set): < 0.1ms per lookup');
        console.log('✅ Scroll to bottom: < 5ms');
        console.log('✅ Memory per 100 messages: < 3 MB');

        return perfResults;
    }

    function compareWithBaseline(baseline) {
        console.log('\n%c=== Comparison with Baseline ===', 'font-size:14px;font-weight:bold;color:#FA896B');

        perfResults.forEach(function(current) {
            var base = baseline.find(function(b) { return b.test === current.test; });
            if (base && base.avg && current.avg) {
                var improvement = ((parseFloat(base.avg) - parseFloat(current.avg)) / parseFloat(base.avg) * 100).toFixed(1);
                var symbol = improvement > 0 ? '✅' : '❌';
                console.log(symbol + ' ' + current.test + ': ' + improvement + '% improvement');
            }
        });
    }

    // Expose public API
    window.ChatPerformanceTest = {
        runAll: runAll,
        compareWithBaseline: compareWithBaseline,
        testBuildMessageHtml: testBuildMessageHtml,
        testAppendMessageToChat: testAppendMessageToChat,
        testDeduplicationPerformance: testDeduplicationPerformance,
        testScrollPerformance: testScrollPerformance,
        testMemoryUsage: testMemoryUsage
    };

    console.log('%c[ChatPerformanceTest] Loaded successfully', 'color:#90bb13;font-weight:bold');
    console.log('Run ChatPerformanceTest.runAll() to start testing');

})();
