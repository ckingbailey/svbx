/*
** Takes arguments:
**  `d3` library
**  `id` of element that will contain pie chart
**      element to contain legend should be next sibling of chart container
**  `d` data in the shape of
**      { 'label': <str>, 'count': <int> }
**      Sorted in the order of colors as they correspond to labels
**  `palette` color palette in the shape of
**      [ '<str> color spec', '<str> color spec', ... ]
**      If more than 2 colors passed,
**      colors will be passed to an interpolator to map them to a color space
**  `width` of pie chart in pixels
**  `height` of pie chart in pixels
**      max dimension will be used
*/
function PieChart(d3, id, d, palette, width = '200', height = '200') {
    const container = document.getElementById(id)

    // in case palette is an object-object
    palette = Object.values(palette)

    // create domain from range of data's indexes
    // use interpolator if more than 2 colors
    const colorPicker = (data, colors) => {
        if (colors.length <= 1)
            throw Error('More than 1 color is required to create color scale. Colors: ' + colors)

        let domain = [ 0, data.length - 1]

        if (colors.length > 2)
            return d3.scaleSequential(domain, d3.interpolateRgbBasis(colors))

        return d3.scaleLinear(domain, colors)
    }
    
    // TODO: setData(), setColors(), setDims(), setWd(), setHt(), setContainer()
    // and get...() for all of the above
    return {
        draw: function() {
            const radius = Math.min(width, height)/2;
            const color = colorPicker(d, palette)
            
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
                
            chart.selectAll('path')
                .data(pie(d))
                .enter()
                .append('path')
                .attr('d', arc)
                .attr('fill', d =>  d3.color(color(d.index)).formatHex())
                
            drawLegend(container.nextElementSibling, d, color);
        },
        getContainer: function() {
            return container;
        },
        getData: () => {
            return d;
        }
    };
    
    function drawLegend(element, d, color) {
        var legend = d3.select(element)
        .append('div')
        .classed('d-flex flex-column flex-wrap mt-3', true)

        var pees = legend.selectAll('p')
        .data(d)
        .enter()
        .append('p')
        .classed('mr-2 mb-1 ml-2', true)
        
        pees.append('i')
        .classed('legend-swatch', true)
        .attr('style', (d, i) => 'background-color:' + color(i))

        pees.append('span')
        .text(d => `${d.count} ${d.label}`)
    }
};
