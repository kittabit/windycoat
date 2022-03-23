import React from 'react';
import ReactDOM from 'react-dom';

import Basic from './Themes/Basic'; 
import Flat from './Themes/Flat'; 

import Footer from './Components/Footer'
import './index.css'; 
 
class App extends React.Component {

  constructor (props){
    super(props);
    this.state = {
      weather_current: [],
      weather_hourly: [],
      weather_daily: [],
      isLoading: 1
    }
  }

  componentDidMount() {
    Promise.all([
      fetch('/wp-json/windycoat/v1/weather/current/'),
      fetch('/wp-json/windycoat/v1/weather/hourly/'),
      fetch('/wp-json/windycoat/v1/weather/daily/')
    ])
    .then(([res1, res2, res3]) => Promise.all([res1.json(), res2.json(), res3.json()]))
    .then(([data1, data2, data3]) => this.setState({
      weather_current: data1, 
      weather_hourly: data2,
      weather_daily: data3,
      isLoading: 0
    }));
  } 

  render(){ 

    return (  

      <div className={'windycoat_container windycoat-theme-' + window.wcSettings.wc_theme}>
         
        {this.state.isLoading ? (
          <div className="windycoat_loading">
            
            <div class="windycoat-load-wrap">
              <div class="windycoat-load">
                <p>Current Weather Loading...</p>
                <div class="windycoat-line"></div>
                <div class="windycoat-line"></div>
                <div class="windycoat-line"></div>
              </div>
            </div>

          </div>
        ) : ( 
          <>           
            {(() => {
              if (window.wcSettings.wc_theme === "flat") {
                return (
                  <Flat weather_current={this.state.weather_current} weather_hourly={this.state.weather_hourly} weather_daily={this.state.weather_daily} />
                )
              } else {
                return (
                  <Basic weather_current={this.state.weather_current} weather_hourly={this.state.weather_hourly} weather_daily={this.state.weather_daily} />
                )
              }
            })()}
            
            <Footer />
          </>
        )}

      </div>

    );

  }

}

const targets = document.querySelectorAll('.wc-root');
Array.prototype.forEach.call(targets, target => {
  ReactDOM.render(React.createElement(App, null), target);
});