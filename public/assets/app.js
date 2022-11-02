const installButton = document.getElementById('install-start');

if (installButton) {
    const installLogCard = document.getElementById('install-log-card');
    const installLogOutput = document.getElementById('install-log-output');
    const installLogError = document.getElementById('install-log-error');
    installButton.onclick = function () {
        installLogCard.style.removeProperty('display');

        installButton.disabled = true;
        const decoder = new TextDecoder();
        fetch(`${baseUrl}/install/_run`)
            .then(async (response) => {
                const reader = response.body.getReader();

                while (true) {
                    const {value, done} = await reader.read();
                    if (done) break;

                    const text = decoder.decode(value);

                    installLogOutput.innerHTML += text;

                    installLogOutput.scrollTop = installLogOutput.scrollHeight;

                    try {
                        const result = JSON.parse(text.split("\n").pop());

                        if (result.newLocation) {
                            window.location = result.newLocation;
                        }

                        if (!result.success) {
                            installLogError.style.removeProperty('display');
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }
            })
    }
}
