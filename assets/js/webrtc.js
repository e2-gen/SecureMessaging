class WebRTCManager {
  constructor() {
    this.peerConnection = new RTCPeerConnection({
      iceServers: [
        { urls: 'stun:stun.l.google.com:19302' }
      ]
    });
  }

  async startCall(chatId) {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
    stream.getTracks().forEach(track => {
      this.peerConnection.addTrack(track, stream);
    });

    const offer = await this.peerConnection.createOffer();
    await this.peerConnection.setLocalDescription(offer);
    
    // Send offer to signaling server
    await fetch(`/api/call/${chatId}/offer`, {
      method: 'POST',
      body: JSON.stringify(offer)
    });
  }

  handleAnswer(answer) {
    this.peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
  }
}