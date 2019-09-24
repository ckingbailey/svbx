/* eventually this module should work by:
    - taking args (elementToSelect, dataToRender)
    - dataToRender will be passed from php MySQL query
    - this will require refactoring fileend.php into a fcn
*/
function PieChart(d3, id, data, palette, width = '200', height = '200') {
    console.log('input data', data)
    // data must come in sorted
    const d = Object.keys(data).map((key) => {
        return {
            label: key,
            count: +data[key]
        }
    })
    console.log('data for d3 consumption', d)
    const container = document.getElementById(id)
    palette = Object.values(palette)
    console.log('color palette', palette)
    // let colors = Object.values(palette);
    const colorPicker = (data, colors) => {
        const d = Object.keys(data)
        if (colors.length <= 1)
            throw Error('More than 1 color is required to create color scale. Colors: ' + colors)
        // if colors data and colors are the same length, create ordinal scale from colors
        if (d.length === colors.length)
            return d3.scaleOrdinal(colors)
        // if more data than colors, scale data range to color range
        if (d.length > colors.length)
            return d3.scaleLinear(
                Array(colors.length).fill(0).map((el, i) => {
                    return (d.length - 1) * (i / (colors.length - 1))
                }),
                colors
            )
    }
    
    // TODO: setData(), setColors(), setDims(), setWd(), setHt(), setContainer()
    // and get...() for all of the above
    return {
        draw: function(options = []) {
            const radius = Math.min(width, height)/2;
            // const color = d3.scaleOrdinal(colors);
            const color = colorPicker(data, palette)
            // d3.scaleQuantize()
            // .domain(Object.keys(data))
            // .range(paletteRange);
            
            const chart = d3.select(container)
                .append('svg')
                .attr('width', width)
                .attr('height', height)
                .append('g')
                // move the group to the middle of the svg element
                .attr('transform', `translate(${width/2},${height/2})`);
                
            const arc = d3.arc()
                .innerRadius(0)
                .outerRadius(radius);
                
            const pie = d3.pie()
                .value(d => d.count)
                .sort(null);
                
            const path = chart.selectAll('path')
                .data(pie(d))
                .enter()
                .append('path')
                .attr('d', arc)
                .attr('fill', d => {
                    console.log('data point', d)
                    return color(d.index)
                });
                
            drawLegend(container, d, palette);
        },
        getContainer: function() {
            return container;
        },
        getData: () => {
            return d;
        }
    };
    
    function drawLegend(container, data, colorScheme) {
        var legend = container.appendChild(document.createElement('div'));
        legend.classList.add('d-flex', 'flex-column', 'flex-wrap', 'mt-3');
    
        data.forEach((datum, i) => {
            const label = legend.appendChild(document.createElement('span'))
            const swatch = document.createElement('i')
            
            label.classList.add('mr-2', 'mb-1', 'ml-2')
            label.textContent = `${datum.count} ${datum.label}`
    
            swatch.classList.add('legend-swatch')
            swatch.style.backgroundColor = colorScheme[i]
            label.insertAdjacentElement('afterbegin', swatch)
        })
    }
};

function drawOpenCloseChart(d3, open, closed) {
    var openCloseData = [
        {label: 'open', count: open},
        {label: 'closed', count: closed}
    ]
    
    var container = document.getElementById('open-closed-graph')
    
    var width = "200"
    var height = "200"
    var radius = Math.min(width, height)/2
    
    var scheme = {
        red: '#d73027',
        green: '#58BF73'
    }
    var color = d3.scaleOrdinal(Object.values(scheme))
    
    var chart = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .append('g')
        .attr('transform', `translate(${width/2},${height/2})`)
        
    var arc = d3.arc()
        .innerRadius(0)
        .outerRadius(radius)
    
    var pie = d3.pie()
        .value(d => d.count)
        .sort(null)
        
    var path = chart.selectAll('path')
        .data(pie(openCloseData))
        .enter()
        .append('path')
        .attr('d', arc)
        .attr('fill', d => color(d.data.label))

    drawLegend(container, openCloseData, Object.values(scheme))
}

function drawSeverityChart(d3, block, crit, maj, min) {
    var severityData = [
        {label: 'blocker', count: block},
        {label: 'critical', count: crit},
        {label: 'major', count: maj},
        {label: 'minor', count: min}
    ]
    var scheme = {
        red: '#bd0026',
        redOrange: '#fc4e2a',
        orange: '#feb24c',
        yellow: '#ffeda0'
    }
    
    var container = document.getElementById('severity-graph')
    
    var width = '200'
    var height = '200'
    var radius = Math.min(width, height)/2
    
    var color = d3.scaleOrdinal(Object.values(scheme))
    
    var chart = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .append('g')
        .attr('transform', `translate(${width/2},${height/2})`)
        
    var arc = d3.arc()
        .innerRadius(0)
        .outerRadius(radius)
    
    var pie = d3.pie()
        .value(d => d.count)
        .sort(null)
        
    var path = chart.selectAll('path')
        .data(pie(severityData))
        .enter()
        .append('path')
        .attr('d', arc)
        .attr('fill', d => color(d.data.label))
        
    drawLegend(container, severityData, Object.values(scheme))
}

function drawLegend(container, data, colorScheme) {
    var legend = container.nextElementSibling

    data.forEach((datum, i) => {
        const label = legend.appendChild(document.createElement('span'))
        const swatch = document.createElement('i')
        
        label.classList.add('legend-label')
        label.textContent = datum.label

        swatch.classList.add('legend-swatch')
        swatch.style.backgroundColor = colorScheme[i]
        label.insertAdjacentElement('afterbegin', swatch)
    })
}
