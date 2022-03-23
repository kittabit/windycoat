import React from 'react';
import Temperature from '../Components/temperature'; 

class Flat extends React.Component {

    render() {
        return (
            <>
                <div className="windycoat_flat_primary_container">
                    <div className="windycoat_flat_primary_upper">
                        <img width="100" height="100" src={`https://openweathermap.org/img/wn/${ this.props.weather_current.icon }@2x.png`} alt={this.props.weather_current.description } loading="lazy" />
                        <span className="windycoat_current_description">{ this.props.weather_current.description }</span>
                    </div>
                    <div className="windycoat_flat_primary_lower">
                        <div class="windycoat_flat_primary_lower_grid_single">
                            <span className="windycoat_current_temp">{ this.props.weather_current.temp }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></span>
                            <span className="windycoat_current_date">{ this.props.weather_current.date }</span>
                        </div>

                        <div class="windycoat_flat_primary_lower_grid_single">
                            <span className="windycoat_low_and_high">
                                <span className="windycoat_low_and_high_inner">
                                    <strong>Low:</strong>
                                    <em>{ this.props.weather_current.temp_min }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></em>
                                </span>
                                <span className="windycoat_low_and_high_inner">
                                    <strong>High:</strong>
                                    <em>{ this.props.weather_current.temp_max }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></em>
                                </span>
                            </span>
                        </div>

                        {this.props.weather_daily.map((item, index) => (
                        <div class="windycoat_flat_primary_lower_grid_single windycoat_flat_primary_lower_grid_single_forecast">
                            <span className="windycoat_forecast_label">{ item.label }</span>
                            <img width="50" height="50" src={`https://openweathermap.org/img/wn/${ item.icon }.png`} alt={ item.description } loading="lazy" />
                            <span className="windycoat_forecast_low_high">{  Math.round(item.temp_low) }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /> / {  Math.round(item.temp_high) }&deg;<Temperature type={window.wcSettings.unit_of_measurement} /></span>
                        </div>     
                        ))}                                           
                    </div>                    
                </div>
            
                <div className="windycoat_flat_secondary_container">
                    {this.props.weather_hourly.map((item, index) => (
                        <div className="windycoat_flat_hourly_single">
                            <div className="windycoat_flat_hourly_single_item windycoat_flat_hourly_single_temp">
                                { Math.round(item.temp) }&deg;<Temperature type={window.wcSettings.unit_of_measurement} />
                            </div>

                            <div className="windycoat_flat_hourly_single_item windycoat_flat_hourly_single_icon">
                                <img width="50" height="50" src={`https://openweathermap.org/img/wn/${ item.icon }.png`} alt={ item.description } loading="lazy" />    
                            </div>

                            <div className="windycoat_flat_hourly_single_item windycoat_flat_hourly_single_time">
                                { item.hour }:00 { item.period }    
                            </div>           

                            <div className="windycoat_flat_hourly_single_item windycoat_flat_hourly_single_pressure">
                                <span className="windycoat_hourly_label">Pressure:</span>
                                { item.pressure }<em className="windycoat_hourly_metric">hPa</em>
                            </div>         

                            <div className="windycoat_flat_hourly_single_item windycoat_flat_hourly_single_humidity">
                                <span className="windycoat_hourly_label">Humidity:</span>
                                { item.humidity }<em className="windycoat_hourly_metric">%</em>   
                            </div>    

                            <div className="windycoat_flat_hourly_single_item windycoat_flat_hourly_single_wind">
                                <span className="windycoat_hourly_label">Wind:</span>
                                { item.wind_speed }<em className="windycoat_hourly_metric">mph</em>
                            </div>                                                                             
                        </div>
                    ))}
                </div> 
        
            </>
        )
    }

}

export default Flat;