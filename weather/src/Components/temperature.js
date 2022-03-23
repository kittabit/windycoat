import React from 'react';

class Temperature extends React.Component {

    render() {

        if( this.props.type === "imperial"){
            return (
                <>
                    F
                </>
            )
        }else if( this.props.type === "standard"){
            return (
                <>
                    K
                </>
            )
        }else if( this.props.type === "metric"){
            return (
                <>
                    C
                </>
            )
        }

    }

}

export default Temperature;