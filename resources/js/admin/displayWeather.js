
        const API_KEY = '1432615e15a14140963113518261203';

        async function fetchWeatherData() {
            const location = document.getElementById('location').value;
            const resultDiv = document.getElementById('weather-result');
            
            resultDiv.innerHTML = 'Loading...';
            
            try {
                const response = await fetch(
                    `http://api.weatherapi.com/v1/forecast.json?key=${API_KEY}&q=${location}&days=1`
                );
                
                if (!response.ok) throw new Error('Failed to fetch');
                
                const data = await response.json();
                displayWeather(data);
            } catch (error) {
                resultDiv.innerHTML = 'Error: ' + error.message;
            }
        }

        function displayWeather(data) {
            const current = data.current;
            const location = data.location;
            
            // Calculate simple fire risk
            let riskScore = 0;
            if (current.temp_c > 30) riskScore += 3;
            if (current.humidity < 30) riskScore += 3;
            if (current.wind_kph > 30) riskScore += 2;
            
            let riskLevel = 'LOW';
            let riskClass = 'risk-low';
            if (riskScore >= 7) {
                riskLevel = 'EXTREME';
                riskClass = 'risk-extreme';
            } else if (riskScore >= 5) {
                riskLevel = 'HIGH';
                riskClass = 'risk-high';
            } else if (riskScore >= 3) {
                riskLevel = 'MODERATE';
                riskClass = 'risk-moderate';
            }
            
            const html = `
                <div class="weather-card ${riskClass}">
                    <h2>${location.name}, ${location.country}</h2>
                    <p>Local Time: ${location.localtime}</p>
                    <h3>Fire Risk Level: ${riskLevel} (${riskScore}/8)</h3>
                    
                    <h4>Current Conditions:</h4>
                    <p>🌡️ Temperature: ${current.temp_c}°C (Feels like: ${current.feelslike_c}°C)</p>
                    <p>💧 Humidity: ${current.humidity}%</p>
                    <p>💨 Wind: ${current.wind_kph} km/h (Gusts: ${current.gust_kph} km/h)</p>
                    <p>🌤️ Condition: ${current.condition.text}</p>
                    <p>☔ Precipitation: ${current.precip_mm} mm</p>
                    <p>🔆 UV Index: ${current.uv}</p>
                    
                    <h4>Fire Risk Factors:</h4>
                    <ul>
                        ${current.temp_c > 30 ? '<li>⚠️ High temperature risk</li>' : ''}
                        ${current.humidity < 30 ? '<li>⚠️ Low humidity risk</li>' : ''}
                        ${current.wind_kph > 30 ? '<li>⚠️ High wind risk</li>' : ''}
                    </ul>
                </div>
            `;
            
            document.getElementById('weather-result').innerHTML = html;
        }
        
        // Auto-load on page load
        window.onload = fetchWeatherData;
