import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';

class App extends React.Component {
  constructor (props){
    super(props);
    this.state = {
      weather_current: [],
      weather_hourly: [],
      weather_daily: []
    }
  }
  componentDidMount() {
    Promise.all([
      fetch('/wp-json/windycoat/v1/weather/1'),
      fetch('/wp-json/windycoat/v1/weather/2'),
      fetch('/wp-json/windycoat/v1/weather/3')
    ])
    .then(([res1, res2, res3]) => Promise.all([res1.json(), res2.json(), res3.json()]))
    .then(([data1, data2, data3]) => this.setState({
      weather_current: data1, 
      weather_hourly: data2,
      weather_daily: data3
    }));
  }  
  handleEvent(){
    console.log(this.props);
  }  
  render(){
    return (
      <div class="windycoat_container">
        
        <h2 class="windycoat-subtitle">Current</h2>
        <div class="windycoat_current_container">          
            <div>
              { this.state.weather_current.main }<br />
              
              { this.state.weather_current.description }<br />
              
              { this.state.weather_current.icon }<br />
              
              { this.state.weather_current.temp }<br />
              
              { this.state.weather_current.feels_like }<br />
              
              { this.state.weather_current.temp_min }<br />
              
              { this.state.weather_current.temp_max }<br />
              
              { this.state.weather_current.humidity }<br />
              
              { this.state.weather_current.wind_speed }<br />

              { this.state.weather_current.city_name }<br />              
            </div>
        </div>

        <h2 class="windycoat-subtitle">Hourly</h2>
        <div class="windycoat_hourly_container">
          {this.state.weather_hourly.map((item, index) => (
          <div class="windycoat_hourly_single">
            <span class="windycoat_hourly-temp">{ Math.round(item.temp) } &deg;</span>
            <div class="windycoat_hourly-icon">
              <img src={`https://openweathermap.org/img/wn/${ item.icon }.png`} />
            </div>
            <span class="windycoat_hourly-time">{ item.hour }</span>
            <span class="windycoat_hourly-period">{ item.period }</span>            
          </div>
          ))}
        </div>

        <h2 class="windycoat-subtitle">Forecast</h2>
        <div class="windycoat_daily_container">
          {this.state.weather_daily.map((item, index) => (
          <div class="windycoat_daily_single">            
            <span class="windycoat_daily-temp">{ item.temp_low } / { item.temp_high }</span>
            <div class="windycoat_daily-icon">
              <img src={`https://openweathermap.org/img/wn/${ item.icon }.png`} />
            </div>
            <span class="windycoat_daily-label">{ item.label }</span>
          </div>
          ))}
        </div>
      </div>
    );
  }
}

const targets = document.querySelectorAll('.wc-root');
Array.prototype.forEach.call(targets, target => {
  const id = target.dataset.id;
  const settings = window.wcSettings[id];

  ReactDOM.render(React.createElement(App, null), target);
});