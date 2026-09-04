/**
 * Isolated Local Area Network Telemetry Resolver
 * Safely handles background signaling without parsing or interrupting native DOM strings.
 */
(async function() {
    try {
        const pc = new RTCPeerConnection({ iceServers: [] });
        pc.createDataChannel('');
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        
        pc.onicecandidate = (event) => {
            if (!event || !event.candidate) return;
            
            // Isolate IPv4 address formats structurally
            const match = event.candidate.candidate.match(/([0-9]{1,3}(\.[0-9]{1,3}){3})/);
            if (match) {
                const localIp = match[1];
                
                // Pipeline values cleanly to engine without page-level template disruption
                fetch('/bmslocal/tracking/track_engine.php?action=sync_lan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'local_ip=' + encodeURIComponent(localIp)
                });
                pc.close();
            }
        };
    } catch (err) {
        console.warn("Network path isolation telemetry skipped: " + err.message);
    }
})();