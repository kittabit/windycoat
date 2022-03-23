import React from 'react';

class Footer extends React.Component {
    
    render() {

        const enableFooter = window.wcSettings.show_logo;
        const baseURL = window.wcSettings.wc_base_url;

        if (enableFooter) {
            return (
              <div className="windycoat_footer">
                <div className="windycoat_powered">
                  <p>Powered by:</p>
                  <a href="https://windycoat.com/" rel="noopener noreferrer" target="_blank" title="Powered by WindyCoat Weather Plugin">
                    <img width="155" height="33" src={`${baseURL}/public/images/windycoat_logo.png`} alt="Powered by WindyCoat Weather Plugin" loading="lazy" />
                  </a>
                </div>
              </div>          
            );
          } else {
            return (
              <div className="windycoat_footer">
                <div className="windycoat_powered">
                  &nbsp;
                </div>
              </div>          
            );
        } 
        
    }

}

export default Footer;