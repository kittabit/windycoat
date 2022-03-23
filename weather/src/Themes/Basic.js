import React from 'react';
import Temperature from '../Components/temperature'; 

class Basic extends React.Component {

    render() {
        return (
            <>
                <div className="windycoat_current_container">          
                    <div className="windycoat_current_container_left">
                        <h2>Currently</h2>
                        <img width="100" height="100" src={`https://openweathermap.org/img/wn/${ this.props.weather_current.icon }@2x.png`} alt={this.props.weather_current.description } loading="lazy" />
                        <span className="windycoat_current_description">{ this.props.weather_current.description }</span>

                        <div className="windycoat_50_50_grid"> 
                            <div className="windycoat_50_50_grid_column">
                            <span className="windycoat_grid_column_title">Current</span>
                            <span className="windycoat_grid_column_value">{ this.props.weather_current.temp }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></span>
                            </div>
                            <div className="windycoat_50_50_grid_column">
                            <span className="windycoat_grid_column_title">Feels Like</span>
                            <span className="windycoat_grid_column_value">{ this.props.weather_current.feels_like }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></span>
                            </div>                
                        </div>
                    </div>
                    <div className="windycoat_current_container_right">
                        <h2>{ this.props.weather_current.date }</h2>
                        
                        <div className="windycoat_50_50_grid">
                        <div className="windycoat_50_50_grid_column">
                            <span className="windycoat_grid_column_title">Low</span>
                            <span className="windycoat_grid_column_value">{ this.props.weather_current.temp_min }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></span>
                        </div>
                        <div className="windycoat_50_50_grid_column">
                            <span className="windycoat_grid_column_title">High</span>
                            <span className="windycoat_grid_column_value">{ this.props.weather_current.temp_max }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></span>
                        </div>                
                        </div>               

                        <div className="windycoat_33_grid">
                        <div className="windycoat_33_grid_column">
                            <span className="windycoat_grid_column_title">Pressure</span>
                            <span className="windycoat_grid_column_value">{ this.props.weather_current.pressure }hPa</span>
                        </div>
                        <div className="windycoat_33_grid_column">
                            <span className="windycoat_grid_column_title">Humidity</span>
                            <span className="windycoat_grid_column_value">{ this.props.weather_current.humidity }%</span>
                        </div>                
                        <div className="windycoat_33_grid_column">
                            <span className="windycoat_grid_column_title">Wind Speed</span>
                            <span className="windycoat_grid_column_value">{ this.props.weather_current.wind_speed }mph</span>
                        </div>                                
                        </div>                      
                    </div>
                </div>

                <h2 className="windycoat-subtitle">Hourly Forecast</h2>
                <div className="windycoat_hourly_container">
                    {this.props.weather_hourly.map((item, index) => (
                    <div className="windycoat_hourly_single">
                    <span className="windycoat_hourly-temp">{ Math.round(item.temp) }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></span>
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
                    {this.props.weather_daily.map((item, index) => (
                    <div className="windycoat_daily_single">            
                    <span className="windycoat_daily-temp">{  Math.round(item.temp_low) }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /> / {  Math.round(item.temp_high) }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></span>
                    <div className="windycoat_daily-icon">
                        <img width="50" height="50" src={`https://openweathermap.org/img/wn/${ item.icon }.png`} alt={ item.description } loading="lazy" />
                    </div>
                    <span className="windycoat_daily-label">{ item.label }</span>
                    </div>
                    ))}
                </div>
            </>
        )
    }

}

export default Basic;