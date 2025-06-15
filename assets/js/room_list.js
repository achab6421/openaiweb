/*  room_list.js  */
/* ============== */
/* 本檔案會在房間列表畫面用到
   功能：
   1. 公開房間   ─ 點「加入房間」直接送出 invite_code
   2. 私密房間   ─ showPwdModal() → 輸入密碼後送 invite_code + room_password
   3. 建立/編輯房間頁面 ─ 勾/取消「私人房間」時同步鎖定密碼欄位
   4. 房主頁面   ─ 點擊密碼方塊自動複製密碼
*/

/*------------------------------------------------------
 | 確保 DOM 讀完再執行
 *----------------------------------------------------*/
document.addEventListener('DOMContentLoaded', function () {

    /*--------------------------------------------------
     | 1. 私密房：在 Modal 送出密碼
     *------------------------------------------------*/
    const pwdForm = document.getElementById('pwdForm');
    if (pwdForm) {
        pwdForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const code = document.getElementById('modalInviteCode').value;
            const pwd  = document.getElementById('modalRoomPassword').value;

            console.log('[JS-DEBUG] submit private → code=', code, ' pwd=', pwd);

            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'room.php?code=' + encodeURIComponent(code);
            // 不把代碼寫在 URL

            const inputCode = document.createElement('input');
            inputCode.type  = 'hidden';
            inputCode.name  = 'invite_code';
            inputCode.value = code;
            form.appendChild(inputCode);

            const inputPwd  = document.createElement('input');
            inputPwd.type  = 'hidden';
            inputPwd.name  = 'room_password';
            inputPwd.value = pwd;
            form.appendChild(inputPwd);

            document.body.appendChild(form);
            form.submit();
        });
    }

    /*--------------------------------------------------
     | 2. 公開房：按鈕直接送 invite_code
     *------------------------------------------------*/
    document.querySelectorAll('.btn-join[data-code]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const code = this.dataset.code;
            if (!code) {
                alert('房間代碼缺失');     // 不該發生，出現就代表按鈕少 data-code
                return;
            }

            console.log('[JS-DEBUG] submit public  → code=', code);

            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'room.php';

            const inputCode = document.createElement('input');
            inputCode.type  = 'hidden';
            inputCode.name  = 'invite_code';
            inputCode.value = code;
            form.appendChild(inputCode);

            document.body.appendChild(form);
            form.submit();
        });
    });

    /*--------------------------------------------------
     | 3. 建立/編輯房：勾選「私人房間」時鎖定密碼欄
     *------------------------------------------------*/
    const privateRoomChk = document.getElementById('privateRoom');
    if (privateRoomChk) {
        privateRoomChk.addEventListener('change', function () {
            const pwdInput = document.querySelector('input[name="room_password"]');
            if (pwdInput) {
                pwdInput.disabled = !this.checked;
                if (!this.checked) pwdInput.value = '';
            }
        });
    }

    /*--------------------------------------------------
     | 4. 房主頁：點擊方塊複製密碼
     *------------------------------------------------*/
    const pwdBox = document.getElementById('roomPasswordBox');
    if (pwdBox) {
        pwdBox.onclick = () => {
            const pwd = document.getElementById('roomPasswordText').textContent;
            navigator.clipboard.writeText(pwd);
            const tip = document.getElementById('pwdCopied');
            if (tip) {
                tip.style.display = 'block';
                setTimeout(() => (tip.style.display = 'none'), 1200);
            }
        };
    }

    /*--------------------------------------------------
     | 5. 私密房：顯示輸入密碼 Modal
     *   ※ 這函式要掛到 window 讓 inline onclick 可以呼叫
     *------------------------------------------------*/
    window.showPwdModal = invite_code => {
        console.log('[JS-DEBUG] showPwdModal →', invite_code);

        document.getElementById('modalInviteCode').value = invite_code;
        document.getElementById('modalRoomPassword').value = '';

        const modal = new bootstrap.Modal(document.getElementById('pwdModal'));
        modal.show();
    };
});
