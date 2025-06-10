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
const db = firebase.database();

function randomCode() {
  // 使用 crypto.getRandomValues 產生 7 位數字（更安全且不重複）
  const arr = new Uint32Array(1);
  window.crypto.getRandomValues(arr);
  return arr[0].toString().padStart(7, '0').slice(0, 7);
}

// 載入 SweetAlert2
// 在 index.php <head> 加入：<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

// 建立房間
document.getElementById('createRoomBtn').onclick = async function() {
  const name = document.getElementById('roomName').value.trim();
  // 新增公開/私人選擇
  const { value: isPublic } = await Swal.fire({
    title: '選擇房間類型',
    input: 'radio',
    inputOptions: {
      '1': '公開（所有人可見）',
      '0': '私人（僅邀請可見）'
    },
    inputValue: '1',
    confirmButtonText: '建立',
    inputValidator: (value) => value === null ? '請選擇房間類型' : undefined
  });
  if (!name) return Swal.fire({icon:'warning', title:'請輸入房間名稱'});
  if (isPublic === undefined) return;
  const code = randomCode();
  let user = window.lobbyUser;
  if (!user || user === "訪客") {
    const { value: nickname } = await Swal.fire({
      title: '請輸入你的暱稱',
      input: 'text',
      inputPlaceholder: '暱稱',
      confirmButtonText: '確定',
      showCancelButton: true,
      inputValidator: (value) => !value && '請輸入暱稱'
    });
    if (!nickname) return;
    user = nickname;
  }
  db.ref('rooms/' + code).set({
    name: name,
    code: code,
    owner: user,
    is_public: isPublic === '1',
    members: { [user]: true },
    createdAt: Date.now()
  });
  window.location.href = 'room.php?code=' + code;
};

// 加入房間
document.getElementById('joinRoomBtn').onclick = async function() {
  const code = document.getElementById('joinCode').value.trim();
  if (!code) return Swal.fire({icon:'warning', title:'請輸入代碼'});
  let user = window.lobbyUser;
  if (!user || user === "訪客") {
    const { value: nickname } = await Swal.fire({
      title: '請輸入你的暱稱',
      input: 'text',
      inputPlaceholder: '暱稱',
      confirmButtonText: '確定',
      showCancelButton: true,
      inputValidator: (value) => !value && '請輸入暱稱'
    });
    if (!nickname) return;
    user = nickname;
  }
  db.ref('rooms/' + code).once('value').then(snap => {
    const room = snap.val();
    if (!room) return Swal.fire({icon:'error', title:'房間不存在'});
    const members = room.members ? Object.keys(room.members) : [];
    if (members.length >= 4) return Swal.fire({icon:'error', title:'房間已滿'});
    if (members.includes(user)) {
      // 直接進入房間頁面
      window.location.href = 'room.php?code=' + code;
      return;
    }
    db.ref('rooms/' + code + '/members/' + user).set(true).then(() => {
      window.location.href = 'room.php?code=' + code;
    });
  });
};

// 房間列表（只顯示公開房間）
db.ref('rooms').on('value', snap => {
  const rooms = snap.val() || {};
  let html = '';
  const user = window.lobbyUser;
  Object.values(rooms).forEach(room => {
    // 只顯示公開房間，或自己是成員（即使是私人房間）
    const members = room.members ? Object.keys(room.members) : [];
    if (!room.is_public && !members.includes(user)) return;

    let btnHtml = '';
    if (members.includes(user)) {
      btnHtml = `<button class="btn btn-primary btn-sm ms-2 enter-room-btn" data-code="${room.code}">進入</button>`;
      if (room.owner === user) {
        btnHtml += `
          <button class="btn btn-danger btn-sm ms-2 delete-room-btn" data-code="${room.code}">刪除</button>
          <button class="btn btn-info btn-sm ms-2 invite-room-btn" data-code="${room.code}" data-room-name="${room.name}">邀請</button>
          <button class="btn btn-secondary btn-sm ms-2 toggle-public-btn" data-code="${room.code}" data-public="${room.is_public ? 1 : 0}">
            ${room.is_public ? '設為私人' : '設為公開'}
          </button>
          <button class="btn btn-warning btn-sm ms-2 edit-room-btn" data-code="${room.code}"><i class="fas fa-edit"></i> 編輯</button>
        `;
      }
    } else if (members.length < 4) {
      btnHtml = `<button class="btn btn-success btn-sm ms-2 join-room-btn" data-code="${room.code}">加入</button>`;
    } else {
      btnHtml = `<button class="btn btn-secondary btn-sm ms-2" disabled>已滿</button>`;
    }
    // 房主可踢人：成員列表顯示踢出按鈕
    let memberListHtml = members.map(m =>
      (room.owner === user && m !== user)
        ? `${m} <button class="btn btn-sm btn-outline-danger kick-member-btn" data-code="${room.code}" data-member="${m}" title="踢出"><i class="fas fa-user-times"></i></button>`
        : m
    ).join('、');
    html += `<div class="room-card mb-2" data-room-name="${room.name}" data-room-code="${room.code}">
      <div><b>${room.name}</b> (${room.code})</div>
      <div>玩家：${memberListHtml}</div>
      <div>
        <span class="badge bg-info">${members.length}/4</span>
        ${room.is_public ? `<span class="badge bg-success ms-2">公開</span>` : `<span class="badge bg-secondary ms-2">私人</span>`}
        ${room.is_public ? `<span class="badge bg-warning ms-2">邀請碼: ${room.code}</span>` : ''}
        ${btnHtml}
      </div>
    </div>`;
  });
  document.getElementById('roomList').innerHTML = html || '<div class="text-muted">目前沒有房間</div>';

  // 綁定加入按鈕事件
  document.querySelectorAll('.join-room-btn').forEach(btn => {
    btn.onclick = async function(e) {
      e.preventDefault();
      const code = this.getAttribute('data-code');
      let user = window.lobbyUser;
      if (!user || user === "訪客") {
        const { value: nickname } = await Swal.fire({
          title: '請輸入你的暱稱',
          input: 'text',
          inputPlaceholder: '暱稱',
          confirmButtonText: '確定',
          showCancelButton: true,
          inputValidator: (value) => !value && '請輸入暱稱'
        });
        if (!nickname) return;
        user = nickname;
      }
      db.ref('rooms/' + code).once('value').then(snap => {
        const room = snap.val();
        if (!room) return Swal.fire({icon:'error', title:'房間不存在'});
        const members = room.members ? Object.keys(room.members) : [];
        if (members.length >= 4) return Swal.fire({icon:'error', title:'房間已滿'});
        if (members.includes(user)) {
          // 已經是成員，直接顯示進入按鈕（不自動進入房間）
          Swal.fire({
            icon: 'info',
            title: '你已經在這個房間',
            text: '請點擊「進入」按鈕進入房間'
          });
          return;
        }
        db.ref('rooms/' + code + '/members/' + user).set(true).then(() => {
          // 加入成功後自動刷新房間列表，讓「進入」按鈕出現
          db.ref('rooms').once('value', () => {});
          Swal.fire({
            icon: 'success',
            title: '加入成功！',
            text: '現在可以點擊「進入」按鈕進入房間'
          });
        });
      });
    };
  });

  // 綁定進入按鈕事件
  document.querySelectorAll('.enter-room-btn').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      const code = this.getAttribute('data-code');
      window.location.href = 'room.php?code=' + code;
    };
  });

  // 綁定刪除按鈕事件（只有房主才會看到）
  document.querySelectorAll('.delete-room-btn').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      const code = this.getAttribute('data-code');
      Swal.fire({
        title: '確定要刪除這個房間嗎？',
        text: '刪除後將無法恢復，且所有成員都會被移除。',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '刪除',
        cancelButtonText: '取消'
      }).then((result) => {
        if (result.isConfirmed) {
          db.ref('rooms/' + code).once('value').then(snap => {
            const room = snap.val();
            if (!room) return Swal.fire({icon:'error', title:'房間不存在'});
            if (room.owner !== window.lobbyUser) {
              Swal.fire({icon:'error', title:'只有房主可以刪除房間'});
              return;
            }
            db.ref('rooms/' + code).remove().then(() => {
              Swal.fire({icon:'success', title:'房間已刪除'});
            });
          });
        }
      });
    };
  });

  // 綁定邀請按鈕事件
  document.querySelectorAll('.invite-room-btn').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      const code = this.getAttribute('data-code');
      const roomName = this.getAttribute('data-room-name');
      const inviteLink = `${window.location.origin}/room.php?code=${code}`;
      navigator.clipboard.writeText(inviteLink).then(() => {
        Swal.fire({
          icon: 'success',
          title: '已複製邀請連結',
          text: `房間名稱：${roomName}\n邀請連結：${inviteLink}`
        });
      }, () => {
        Swal.fire({icon:'error', title:'複製失敗'});
      });
    };
  });

  // 綁定公開/私人切換按鈕（房主）
  document.querySelectorAll('.toggle-public-btn').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      const code = this.getAttribute('data-code');
      const isPublic = this.getAttribute('data-public') === '1';
      db.ref('rooms/' + code + '/is_public').set(!isPublic).then(() => {
        Swal.fire({
          icon: 'success',
          title: isPublic ? '已設為私人房間' : '已設為公開房間'
        });
      });
    };
  });

  // 綁定踢出成員按鈕（房主）
  document.querySelectorAll('.kick-member-btn').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      const code = this.getAttribute('data-code');
      const member = this.getAttribute('data-member');
      Swal.fire({
        title: `確定要將 ${member} 踢出房間嗎？`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '踢出',
        cancelButtonText: '取消'
      }).then(result => {
        if (result.isConfirmed) {
          db.ref(`rooms/${code}/members/${member}`).remove().then(() => {
            Swal.fire({icon:'success', title:`${member} 已被踢出`});
          });
        }
      });
    };
  });
});

// 房主編輯房間功能（名稱、公開/私人）
document.addEventListener('click', async function(e) {
  const editBtn = e.target.closest('.edit-room-btn');
  if (editBtn) {
    e.preventDefault();
    const code = editBtn.getAttribute('data-code');
    db.ref('rooms/' + code).once('value').then(async snap => {
      const room = snap.val();
      if (!room) return Swal.fire({icon:'error', title:'房間不存在'});
      if (room.owner !== window.lobbyUser) {
        Swal.fire({icon:'error', title:'只有房主可以編輯房間'});
        return;
      }
      const { value: formValues } = await Swal.fire({
        title: '編輯房間',
        html:
          `<input id="swal-room-name" class="swal2-input" placeholder="房間名稱" value="${room.name}">` +
          `<div class="form-check mt-2" style="text-align:left;">
            <input class="form-check-input" type="radio" name="swal-room-public" id="swal-public" value="1" ${room.is_public ? 'checked' : ''}>
            <label class="form-check-label" for="swal-public">公開</label>
          </div>
          <div class="form-check" style="text-align:left;">
            <input class="form-check-input" type="radio" name="swal-room-public" id="swal-private" value="0" ${!room.is_public ? 'checked' : ''}>
            <label class="form-check-label" for="swal-private">私人</label>
          </div>`,
        focusConfirm: false,
        preConfirm: () => {
          const name = document.getElementById('swal-room-name').value.trim();
          const isPublic = document.getElementById('swal-public').checked ? true : false;
          if (!name) {
            Swal.showValidationMessage('請輸入房間名稱');
            return false;
          }
          return { name, isPublic };
        },
        confirmButtonText: '儲存',
        showCancelButton: true
      });
      if (!formValues) return;
      db.ref('rooms/' + code).update({
        name: formValues.name,
        is_public: formValues.isPublic
      }).then(() => {
        Swal.fire({icon:'success', title:'房間已更新'});
      });
    });
  }
});

// 建立房間（彈框）
async function showCreateRoomModal() {
  const { value: formValues } = await Swal.fire({
    title: '建立房間',
    html:
      `<input id="swal-room-name" class="swal2-input" placeholder="房間名稱">` +
      `<div class="form-check mt-2" style="text-align:left;">
        <input class="form-check-input" type="radio" name="swal-room-public" id="swal-public" value="1" checked>
        <label class="form-check-label" for="swal-public">公開</label>
      </div>
      <div class="form-check" style="text-align:left;">
        <input class="form-check-input" type="radio" name="swal-room-public" id="swal-private" value="0">
        <label class="form-check-label" for="swal-private">私人</label>
      </div>
      <div class="mt-2">
        <label>人數上限</label>
        <select id="swal-max-players" class="form-select mt-1">
          <option value="1">1人</option>
          <option value="2">2人</option>
          <option value="3">3人</option>
          <option value="4" selected>4人</option>
        </select>
      </div>
      <input id="swal-room-password" class="swal2-input" placeholder="密碼（僅私人房間需填）" style="display:none;">`,
    focusConfirm: false,
    preConfirm: () => {
      const name = document.getElementById('swal-room-name').value.trim();
      const isPublic = document.getElementById('swal-public').checked ? true : false;
      const maxPlayers = document.getElementById('swal-max-players').value;
      const password = document.getElementById('swal-room-password').value.trim();
      if (!name) {
        Swal.showValidationMessage('請輸入房間名稱');
        return false;
      }
      if (!isPublic && !password) {
        Swal.showValidationMessage('私人房間需填寫密碼');
        return false;
      }
      return { name, isPublic, maxPlayers, password };
    },
    didOpen: () => {
      // 切換顯示密碼欄
      document.getElementById('swal-public').addEventListener('change', function() {
        document.getElementById('swal-room-password').style.display = 'none';
      });
      document.getElementById('swal-private').addEventListener('change', function() {
        document.getElementById('swal-room-password').style.display = '';
      });
    },
    confirmButtonText: '建立',
    showCancelButton: true
  });
  if (!formValues) return;
  // 建立房間
  const code = randomCode();
  let user = window.lobbyUser;
  if (!user || user === "訪客") {
    const { value: nickname } = await Swal.fire({
      title: '請輸入你的暱稱',
      input: 'text',
      inputPlaceholder: '暱稱',
      confirmButtonText: '確定',
      showCancelButton: true,
      inputValidator: (value) => !value && '請輸入暱稱'
    });
    if (!nickname) return;
    user = nickname;
  }
  db.ref('rooms/' + code).set({
    name: formValues.name,
    code: code,
    owner: user,
    is_public: formValues.isPublic,
    max_players: parseInt(formValues.maxPlayers),
    password: formValues.isPublic ? "" : formValues.password,
    members: { [user]: true },
    createdAt: Date.now()
  });
  window.location.href = 'room.php?code=' + code;
}

// 加入房間（彈框）
async function showJoinRoomModal() {
  const { value: joinValues } = await Swal.fire({
    title: '加入房間',
    html:
      `<input id="swal-join-code" class="swal2-input" placeholder="請輸入房間ID">` +
      `<input id="swal-join-password" class="swal2-input" placeholder="密碼（如有）" type="password">`,
    focusConfirm: false,
    preConfirm: () => {
      const code = document.getElementById('swal-join-code').value.trim();
      const password = document.getElementById('swal-join-password').value.trim();
      if (!code) {
        Swal.showValidationMessage('請輸入房間ID');
        return false;
      }
      return { code, password };
    },
    confirmButtonText: '加入',
    showCancelButton: true
  });
  if (!joinValues) return;
  let user = window.lobbyUser;
  if (!user || user === "訪客") {
    const { value: nickname } = await Swal.fire({
      title: '請輸入你的暱稱',
      input: 'text',
      inputPlaceholder: '暱稱',
      confirmButtonText: '確定',
      showCancelButton: true,
      inputValidator: (value) => !value && '請輸入暱稱'
    });
    if (!nickname) return;
    user = nickname;
  }
  db.ref('rooms/' + joinValues.code).once('value').then(snap => {
    const room = snap.val();
    if (!room) return Swal.fire({icon:'error', title:'房間不存在'});
    if (!room.is_public && room.password !== joinValues.password) {
      return Swal.fire({icon:'error', title:'密碼錯誤'});
    }
    const members = room.members ? Object.keys(room.members) : [];
    if (members.length >= (room.max_players || 4)) return Swal.fire({icon:'error', title:'房間已滿'});
    if (members.includes(user)) {
      window.location.href = 'room.php?code=' + joinValues.code;
      return;
    }
    db.ref('rooms/' + joinValues.code + '/members/' + user).set(true).then(() => {
      window.location.href = 'room.php?code=' + joinValues.code;
    });
  });
}

// 房間列表（彈框）
async function showRoomListModal() {
  // 取得所有房間
  const snap = await db.ref('rooms').once('value');
  const rooms = snap.val() || {};
  const user = window.lobbyUser;
  let html = '';
  Object.values(rooms).forEach(room => {
    const members = room.members ? Object.keys(room.members) : [];
    // 公開房間 or 自己已加入的私有房間
    if (room.is_public || members.includes(user)) {
      html += `<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center">
        <div>
          <b>${room.name}</b> (${room.code})<br>
          <span class="badge bg-info">${members.length}/${room.max_players || 4}</span>
          ${room.is_public ? '<span class="badge bg-success ms-2">公開</span>' : '<span class="badge bg-secondary ms-2">私人</span>'}
        </div>
        <button class="btn btn-primary btn-sm" onclick="window.location.href='room.php?code=${room.code}'">進入</button>
      </div>`;
    }
  });
  Swal.fire({
    title: '房間列表',
    html: html || '<div class="text-muted">目前沒有可加入的房間</div>',
    width: 600,
    showConfirmButton: false,
    showCloseButton: true
  });
}

// 綁定主頁按鈕
document.addEventListener('DOMContentLoaded', function() {
  const btnCreate = document.querySelector('.btn-create');
  const btnJoin = document.querySelector('.btn-join');
  const btnList = document.querySelector('.btn-list');
  if (btnCreate) btnCreate.onclick = showCreateRoomModal;
  if (btnJoin) btnJoin.onclick = showJoinRoomModal;
  if (btnList) btnList.onclick = showRoomListModal;
});

// ...保留原本的房間管理、列表、踢人等功能...
