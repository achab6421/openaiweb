

<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
<script>
const firebaseConfig = {
    apiKey: "AIzaSyCCLkT0VSweTF1-w_ecMybR7WdnvHs0oKA",
    authDomain: "openai-dbd3b.firebaseapp.com",
    databaseURL: "https://openai-dbd3b-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "openai-dbd3b",
    storageBucket: "openai-dbd3b.appspot.com",
    messagingSenderId: "977828405782",
    appId: "1:977828405782:web:eeb71ec2d11c7edfa10b37",
    measurementId: "G-Y5DJ7B9LXM"
};
firebase.initializeApp(firebaseConfig);
firebase.auth().signInAnonymously().catch(console.error);

// 拿房間代碼、暱稱
const teamCode = "<?= htmlspecialchars($room['invite_code']) ?>";
const username = "<?= htmlspecialchars($_SESSION['username']) ?>";
const chatRef = firebase.database().ref('team_chats/' + teamCode);

// 顯示訊息
chatRef.limitToLast(50).on('child_added', function(snapshot) {
  const msg = snapshot.val();
  const msgDiv = document.createElement('div');
  const now = new Date();
  const t = msg.time || (now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0'));
  msgDiv.innerHTML = `<span style="color:#ffe4b5;font-weight:bold;">${msg.user}：</span><span style="color:#fff;">${msg.text}</span><span style="color:#888;font-size:0.9em;margin-left:8px;">${t}</span>`;
  document.getElementById('team-chat-messages').appendChild(msgDiv);
  document.getElementById('team-chat-messages').scrollTop = document.getElementById('team-chat-messages').scrollHeight;
});

// 發送訊息
document.getElementById('team-chat-form').addEventListener('submit', function(e) {
  e.preventDefault();
  const input = document.getElementById('team-chat-input');
  const text = input.value.trim();
  if (!text) return;
  const now = new Date();
  const time = now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0');
  chatRef.push({
    user: username,
    text: text,
    time: time
  });
  input.value = '';
});
</script>
