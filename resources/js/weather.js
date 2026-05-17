// Your API key from weatherapi.com
const API_KEY = 'YOUR_API_KEY_HERE';

// Function to fetch current weather
async function getCurrentWeather(location) {
    try {
        const response = await fetch(
            `http://api.weatherapi.com/v1/current.json?key=${API_KEY}&q=${location}&aqi=no`
        );
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching weather data:', error);
    }
}

// Function to fetch forecast (for fire prediction - more useful!)
async function getWeatherForecast(location, days = 3) {
    try {
        const response = await fetch(
            `http://api.weatherapi.com/v1/forecast.json?key=${API_KEY}&q=${location}&days=${days}&aqi=no&alerts=yes`
        );
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching forecast:', error);
    }
}

// Function to fetch historical weather (for training your model)
async function getHistoricalWeather(location, date) {
    try {
        const response = await fetch(
            `http://api.weatherapi.com/v1/history.json?key=${API_KEY}&q=${location}&dt=${date}`
        );
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching historical data:', error);
    }
}

// Example usage
async function main() {
    // Get current weather for a specific location
    const currentWeather = await getCurrentWeather('London');
    console.log('Current Weather:', currentWeather);
    
    // Get 3-day forecast
    const forecast = await getWeatherForecast('New York', 3);
    console.log('Forecast:', forecast);
    
    // Get historical data for a specific date
    const historical = await getHistoricalWeather('Tokyo', '2024-01-15');
    console.log('Historical:', historical);
}

// Run the example
// main();
class FireRiskPredictor {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.baseUrl = 'http://api.weatherapi.com/v1';
    }

    // Fetch weather data for fire risk assessment
    async assessFireRisk(location, materials = []) {
        try {
            // Get forecast for next 24 hours
            const forecast = await this.getDetailedForecast(location);
            
            // Calculate risk based on weather
            const weatherRisk = this.calculateWeatherRisk(forecast);
            
            // Combine with material risk
            const materialRisk = this.calculateMaterialRisk(materials, forecast.current.temp_c);
            
            // Get final risk score
            const riskScore = this.calculateTotalRisk(weatherRisk, materialRisk);
            
            return {
                location: location,
                timestamp: new Date().toISOString(),
                riskLevel: this.getRiskLevel(riskScore),
                riskScore: riskScore,
                weatherData: {
                    temperature: forecast.current.temp_c,
                    humidity: forecast.current.humidity,
                    windSpeed: forecast.current.wind_kph,
                    condition: forecast.current.condition.text,
                    feelsLike: forecast.current.feelslike_c
                },
                forecast: forecast.forecast.forecastday[0].hour.map(hour => ({
                    time: hour.time,
                    temp: hour.temp_c,
                    humidity: hour.humidity,
                    windSpeed: hour.wind_kph,
                    chanceOfRain: hour.chance_of_rain
                })),
                recommendations: this.generateRecommendations(riskScore, weatherRisk, materials)
            };
        } catch (error) {
            console.error('Error assessing fire risk:', error);
            return null;
        }
    }

    // Fetch detailed forecast
    async getDetailedForecast(location) {
        const response = await fetch(
            `${this.baseUrl}/forecast.json?key=${this.apiKey}&q=${location}&days=1&hourly=24`
        );
        
        if (!response.ok) {
            throw new Error('Failed to fetch forecast');
        }
        
        return await response.json();
    }

    // Calculate weather-based risk (0-10 scale)
    calculateWeatherRisk(forecast) {
        const current = forecast.current;
        let risk = 0;
        
        // Temperature risk (higher = more risk)
        if (current.temp_c > 35) risk += 3;
        else if (current.temp_c > 30) risk += 2;
        else if (current.temp_c > 25) risk += 1;
        
        // Humidity risk (lower = more risk)
        if (current.humidity < 20) risk += 3;
        else if (current.humidity < 30) risk += 2;
        else if (current.humidity < 40) risk += 1;
        
        // Wind speed risk (faster = more risk)
        if (current.wind_kph > 40) risk += 3;
        else if (current.wind_kph > 30) risk += 2;
        else if (current.wind_kph > 20) risk += 1;
        
        return Math.min(risk, 10); // Cap at 10
    }

    // Calculate material-based risk (0-10 scale)
    calculateMaterialRisk(materials, currentTemp) {
        if (!materials || materials.length === 0) return 0;
        
        const materialRiskScores = {
            'wood': { risk: 7, tempThreshold: 30 },
            'paper': { risk: 8, tempThreshold: 25 },
            'chemicals': { risk: 9, tempThreshold: 20 },
            'fuel': { risk: 10, tempThreshold: 15 },
            'plastic': { risk: 6, tempThreshold: 35 },
            'textiles': { risk: 5, tempThreshold: 32 }
        };
        
        let maxRisk = 0;
        
        materials.forEach(material => {
            const materialData = materialRiskScores[material.toLowerCase()];
            if (materialData) {
                // Increase risk if temperature is above threshold
                if (currentTemp > materialData.tempThreshold) {
                    maxRisk = Math.max(maxRisk, materialData.risk + 2);
                } else {
                    maxRisk = Math.max(maxRisk, materialData.risk);
                }
            }
        });
        
        return maxRisk;
    }

    // Calculate total risk (average of weather and material)
    calculateTotalRisk(weatherRisk, materialRisk) {
        // If no materials present, just use weather risk
        if (materialRisk === 0) return weatherRisk;
        
        // Weighted average (60% weather, 40% materials)
        return Math.round((weatherRisk * 0.6) + (materialRisk * 0.4));
    }

    // Get risk level based on score
    getRiskLevel(score) {
        if (score >= 8) return 'EXTREME';
        if (score >= 6) return 'HIGH';
        if (score >= 4) return 'MODERATE';
        if (score >= 2) return 'LOW';
        return 'MINIMAL';
    }

    // Generate recommendations based on risk
    generateRecommendations(score, weatherRisk, materials) {
        const recommendations = [];
        
        if (score >= 8) {
            recommendations.push('IMMEDIATE ACTION REQUIRED: Consider evacuating flammable materials');
            recommendations.push('Post fire watch personnel');
            recommendations.push('Ensure all fire suppression systems are operational');
        } else if (score >= 6) {
            recommendations.push('Increase monitoring frequency');
            recommendations.push('Check fire extinguishers and sprinkler systems');
            recommendations.push('Limit hot work activities');
        } else if (score >= 4) {
            recommendations.push('Maintain normal safety protocols');
            recommendations.push('Review emergency procedures with staff');
        }
        
        if (weatherRisk >= 7) {
            recommendations.push('Weather conditions are high risk - increase vigilance');
        }
        
        return recommendations;
    }
}

// Usage example
async function runFireRiskPrediction() {
    const predictor = new FireRiskPredictor('YOUR_API_KEY_HERE');
    
    // Assess fire risk for a warehouse with specific materials
    const riskAssessment = await predictor.assessFireRisk(
        'Los Angeles', 
        ['wood', 'chemicals', 'paper']
    );
    
    console.log('Fire Risk Assessment:');
    console.log('=====================');
    console.log(`Location: ${riskAssessment.location}`);
    console.log(`Risk Level: ${riskAssessment.riskLevel}`);
    console.log(`Risk Score: ${riskAssessment.riskScore}/10`);
    console.log('\nCurrent Weather:');
    console.log(`Temperature: ${riskAssessment.weatherData.temperature}°C`);
    console.log(`Humidity: ${riskAssessment.weatherData.humidity}%`);
    console.log(`Wind Speed: ${riskAssessment.weatherData.windSpeed} km/h`);
    console.log(`Conditions: ${riskAssessment.weatherData.condition}`);
    console.log('\nRecommendations:');
    riskAssessment.recommendations.forEach(rec => console.log(`• ${rec}`));
    
    return riskAssessment;
}

// Run the prediction
runFireRiskPrediction().then(assessment => {
    // You can send this to your database or display it
    console.log('\nFull Assessment:', JSON.stringify(assessment, null, 2));
});