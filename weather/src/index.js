import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';

class App extends React.Component {
  constructor (props){
    super(props);
    this.state = {
      weather_current: [],
      weather_hourly: [],
      weather_daily: [],
      isLoading: 1,
      current_time: (new Date()).toLocaleString()
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
      weather_daily: data3,
      isLoading: 0
    }));
  } 
  handleEvent(){
    console.log(this.props);  
  }   
  render(){
    return ( 
      <div className="windycoat_container">
        
        {this.state.isLoading ? (
          <div className="windycoat_loading">Loading Current Weather...</div>
        ) : (
        <div>
          <div className="windycoat_current_container">          
              <div className="windycoat_current_container_left">
                  <h2>Currently</h2>
                  <img width="100" height="100" src={`https://openweathermap.org/img/wn/${ this.state.weather_current.icon }@2x.png`} alt={this.state.weather_current.description } loading="lazy" />
                  <span className="windycoat_current_description">{ this.state.weather_current.description }</span>

                  <div className="windycoat_50_50_grid">
                    <div className="windycoat_50_50_grid_column">
                      <span className="windycoat_grid_column_title">Current</span>
                      <span className="windycoat_grid_column_value">{ this.state.weather_current.temp }&deg;F</span>
                    </div>
                    <div className="windycoat_50_50_grid_column">
                      <span className="windycoat_grid_column_title">Feels Like</span>
                      <span className="windycoat_grid_column_value">{ this.state.weather_current.feels_like }&deg;F</span>
                    </div>                
                  </div>
              </div>
              <div className="windycoat_current_container_right">
                <h2>{ this.state.weather_current.date }</h2>
                
                <div className="windycoat_50_50_grid">
                  <div className="windycoat_50_50_grid_column">
                    <span className="windycoat_grid_column_title">Low</span>
                    <span className="windycoat_grid_column_value">{ this.state.weather_current.temp_min }&deg;F</span>
                  </div>
                  <div className="windycoat_50_50_grid_column">
                    <span className="windycoat_grid_column_title">High</span>
                    <span className="windycoat_grid_column_value">{ this.state.weather_current.temp_max }&deg;F</span>
                  </div>                
                </div>               

                <div className="windycoat_33_grid">
                  <div className="windycoat_33_grid_column">
                    <span className="windycoat_grid_column_title">Pressure</span>
                    <span className="windycoat_grid_column_value">{ this.state.weather_current.pressure }</span>
                  </div>
                  <div className="windycoat_33_grid_column">
                    <span className="windycoat_grid_column_title">Humidity</span>
                    <span className="windycoat_grid_column_value">{ this.state.weather_current.humidity }</span>
                  </div>                
                  <div className="windycoat_33_grid_column">
                    <span className="windycoat_grid_column_title">Wind Speed</span>
                    <span className="windycoat_grid_column_value">{ this.state.weather_current.wind_speed }mph</span>
                  </div>                                
                </div>                      
              </div>
          </div>

          <h2 className="windycoat-subtitle">Hourly Forecast</h2>
          <div className="windycoat_hourly_container">
            {this.state.weather_hourly.map((item, index) => (
            <div className="windycoat_hourly_single">
              <span className="windycoat_hourly-temp">{ Math.round(item.temp) }&deg;F</span>
              <div className="windycoat_hourly-icon">
                <img width="50" height="50" src={`https://openweathermap.org/img/wn/${ item.icon }.png`} alt={ item.description } loading="lazy" />
              </div>
              <span className="windycoat_hourly-time">{ item.hour }:00</span>
              <span className="windycoat_hourly-period">{ item.period }</span>            
            </div>
            ))}
          </div>
 
          <h2 className="windycoat-subtitle">Upcoming Forecast</h2>
          <div className="windycoat_daily_container">
            {this.state.weather_daily.map((item, index) => (
            <div className="windycoat_daily_single">            
              <span className="windycoat_daily-temp">{  Math.round(item.temp_low) }&deg;F / {  Math.round(item.temp_high) }&deg;F</span>
              <div className="windycoat_daily-icon">
                <img width="50" height="50" src={`https://openweathermap.org/img/wn/${ item.icon }.png`} alt={ item.description } loading="lazy" />
              </div>
              <span className="windycoat_daily-label">{ item.label }</span>
            </div>
            ))}
          </div>
          
          <div className="windycoat_footer">
            <div className="windycoat_powered">
              <p>Powered by:</p>
              <a href="https://windycoat.com/" rel="noopener" target="_blank" title="Powered by WindyCoat Weather Plugin">
                <img width="155" height="33" src="/wp-content/plugins/windycoat/public/images/windycoat_logo.png" alt="Powered by WindyCoat Weather Plugin" loading="lazy" />
              </a>
            </div>
          </div>          
        </div>
        )}
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