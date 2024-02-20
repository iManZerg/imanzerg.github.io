window.onload = function () {
    fetch("https://ipinfo.io/json?token=e808a723ebcba7").then(
        (response) => response.json()
    ).then(
        (jsonResponse) => {
            document.getElementById("city").innerHTML = jsonResponse.city;
            document.getElementById("region").innerHTML = jsonResponse.region;
            document.getElementById("country").innerHTML = jsonResponse.country;
        }

    )
}

// (jsonResponse) => console.log(jsonResponse.ip, jsonResponse.country, jsonResponse.region, jsonResponse.city)