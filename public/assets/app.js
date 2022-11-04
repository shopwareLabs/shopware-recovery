const decoder = new TextDecoder();

async function tailLog(response, element) {
    const reader = response.body.getReader();

    while (true) {
        const {value, done} = await reader.read();
        if (done) break;

        const text = decoder.decode(value);

        try {
            const result = JSON.parse(text.split("\n").pop());

            if (!result.success) {
                throw new Error('update failed');
            }

            return result;
        } catch (e) {
            element.innerHTML += text;
            element.scrollTop = element.scrollHeight;
        }
    }

    throw new Error('Unexpected end of stream');
}

const installButton = document.getElementById('install-start');
const updateButton = document.getElementById('update-start');

if (installButton) {
    const installLogCard = document.getElementById('install-log-card');
    const installLogOutput = document.getElementById('install-log-output');
    const installLogError = document.getElementById('install-log-error');
    installButton.onclick = async function () {
        installLogCard.style.removeProperty('display');

        installButton.disabled = true;

        const instalLResponse = await fetch(`${baseUrl}/install/_run`);

        const result = await tailLog(instalLResponse, installLogOutput);
        if (result.newLocation) {
            window.location = result.newLocation;
        }

        if (!result.success) {
            installLogError.style.removeProperty('display');
        }
    }
}

if (updateButton) {
    const updateLogCard = document.getElementById('update-log-card');
    const updateLogOutput = document.getElementById('update-log-output');

    updateButton.onclick = async function () {
        updateButton.disabled = true;
        updateLogCard.style.removeProperty('display');

        if (!isFlexProject) {
            updateLogOutput.innerHTML += 'Updating to Flex Project' + "\n"

            const migrate = await fetch(`${baseUrl}/update/_migrate-template`)

            if (migrate.status !== 200) {
                updateLogOutput.innerHTML += 'Failed to update to Flex Project' + "\n"
                updateLogCard.innerHTML += await migrate.text();
                return;
            } else {
                updateLogOutput.innerHTML += 'Updated to Flex Project' + "\n"
            }
        }
        
        const prepareUpdate = await fetch(`${baseUrl}/update/_prepare`)
        if (prepareUpdate.status !== 200) {
            updateLogOutput.innerHTML += 'Failed to prepare update' + "\n"
            return;
        } else {
            await tailLog(prepareUpdate, updateLogOutput);
        }

        const updateRun = await fetch(`${baseUrl}/update/_run`);

        await tailLog(updateRun, updateLogOutput);

        const finishUpdate = await fetch(`${baseUrl}/update/_finish`)
        if (finishUpdate.status !== 200) {
            updateLogOutput.innerHTML += 'Failed to prepare update' + "\n"
            updateLogOutput.innerHTML += await finishUpdate.text();
        } else {
            await tailLog(finishUpdate, updateLogOutput);
        }

        window.location.reload();
    }
}

